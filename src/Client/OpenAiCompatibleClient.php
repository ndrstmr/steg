<?php

declare(strict_types=1);

namespace Steg\Client;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Steg\Exception\ConnectionException;
use Steg\Exception\InferenceException;
use Steg\Exception\InvalidResponseException;
use Steg\Exception\ModelNotFoundException;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;
use Steg\Model\CompletionRequest;
use Steg\Model\CompletionResponse;
use Steg\Model\ModelInfo;
use Steg\Model\StreamChunk;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * OpenAI-compatible HTTP client for local inference servers.
 *
 * Supports: vLLM, Ollama (/v1 mode), LiteLLM, LocalAI, llama.cpp server.
 *
 * Requires symfony/http-client (or any HttpClientInterface implementation)
 * at runtime — it is only a "suggest" dependency to keep the core framework-free.
 */
final class OpenAiCompatibleClient implements InferenceClientInterface
{
    private const DEFAULT_TIMEOUT = 120;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly string $apiKey = 'EMPTY',
        private readonly int $timeout = self::DEFAULT_TIMEOUT,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @param list<ChatMessage> $messages
     *
     * @throws ConnectionException      When the server is unreachable or times out
     * @throws ModelNotFoundException   When the requested model is not loaded
     * @throws InferenceException       When the server returns 4xx/5xx
     * @throws InvalidResponseException When the response cannot be parsed
     */
    public function complete(array $messages, ?CompletionOptions $options = null): CompletionResponse
    {
        $request = CompletionRequest::create($messages, $this->model, $options);
        $payload = $request->toArray();

        $this->logger->debug('Steg: sending completion request', [
            'model' => $this->model,
            'messages' => \count($messages),
        ]);

        $startTime = microtime(true);

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl.'/chat/completions', [
                'headers' => $this->buildHeaders(),
                'json' => $payload,
                'timeout' => $this->timeout,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw ConnectionException::unreachable($this->baseUrl, $e);
        }

        $durationMs = (microtime(true) - $startTime) * 1000;

        $this->handleErrorResponse($statusCode, $body);

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw InvalidResponseException::malformedJson($body, $e);
        }

        $this->logger->debug('Steg: completion received', [
            'duration_ms' => round($durationMs, 2),
        ]);

        return CompletionResponse::fromApiResponse($data, $durationMs);
    }

    /**
     * @param list<ChatMessage> $messages
     *
     * @return \Generator<int, StreamChunk, mixed, void>
     *
     * @throws ConnectionException      When the server is unreachable or times out
     * @throws ModelNotFoundException   When the requested model is not loaded
     * @throws InferenceException       When the server returns 4xx/5xx
     * @throws InvalidResponseException When a stream chunk cannot be parsed
     */
    public function stream(array $messages, ?CompletionOptions $options = null): \Generator
    {
        $request = CompletionRequest::create($messages, $this->model, $options);
        $payload = array_merge($request->toArray(), ['stream' => true]);

        try {
            $response = $this->httpClient->request('POST', $this->baseUrl.'/chat/completions', [
                'headers' => $this->buildHeaders(),
                'json' => $payload,
                'timeout' => $this->timeout,
                'buffer' => false,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode >= 400) {
                $body = $response->getContent(false);
                $this->handleErrorResponse($statusCode, $body);
            }

            foreach ($this->httpClient->stream($response) as $chunk) {
                if ($chunk->isLast()) {
                    break;
                }

                $raw = trim($chunk->getContent());
                foreach (explode("\n", $raw) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) {
                        continue;
                    }

                    $jsonStr = substr($line, 6);
                    if ('[DONE]' === $jsonStr) {
                        return;
                    }

                    try {
                        /** @var array<string, mixed> $data */
                        $data = json_decode($jsonStr, true, 512, \JSON_THROW_ON_ERROR);
                    } catch (\JsonException $e) {
                        throw InvalidResponseException::malformedJson($jsonStr, $e);
                    }

                    $streamChunk = StreamChunk::fromSseData($data);
                    yield $streamChunk;

                    if ($streamChunk->isLast) {
                        return;
                    }
                }
            }
        } catch (TransportExceptionInterface $e) {
            throw ConnectionException::unreachable($this->baseUrl, $e);
        }
    }

    /**
     * @return list<ModelInfo>
     *
     * @throws ConnectionException      When the server is unreachable
     * @throws InferenceException       When the server returns an error
     * @throws InvalidResponseException When the response cannot be parsed
     */
    public function listModels(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl.'/models', [
                'headers' => $this->buildHeaders(),
                'timeout' => $this->timeout,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw ConnectionException::unreachable($this->baseUrl, $e);
        }

        $this->handleErrorResponse($statusCode, $body);

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw InvalidResponseException::malformedJson($body, $e);
        }

        if (!isset($data['data']) || !\is_array($data['data'])) {
            throw InvalidResponseException::missingField('data');
        }

        return array_values(array_map(
            static fn (mixed $item) => ModelInfo::fromApiResponse(\is_array($item) ? $item : []),
            $data['data'],
        ));
    }

    /**
     * Returns true if the server is reachable and responds with HTTP 200.
     * Never throws — returns false on any failure.
     */
    public function isHealthy(): bool
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl.'/models', [
                'headers' => $this->buildHeaders(),
                'timeout' => 5,
            ]);

            return $response->getStatusCode() < 400;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function handleErrorResponse(int $statusCode, string $body): void
    {
        if ($statusCode < 400) {
            return;
        }

        if (404 === $statusCode) {
            throw ModelNotFoundException::forModel($this->model);
        }

        throw InferenceException::fromHttpResponse($statusCode, $body);
    }
}
