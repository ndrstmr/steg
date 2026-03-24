<?php

declare(strict_types=1);

namespace Steg\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Steg\Client\OpenAiCompatibleClient;
use Steg\Exception\ConnectionException;
use Steg\Exception\InferenceException;
use Steg\Exception\InvalidResponseException;
use Steg\Exception\ModelNotFoundException;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenAiCompatibleClientTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeSuccessResponse(string $content = 'Test response', string $model = 'llama-3.3-70b'): string
    {
        return json_encode([
            'id' => 'chatcmpl-test',
            'object' => 'chat.completion',
            'model' => $model,
            'choices' => [
                [
                    'index' => 0,
                    'message' => ['role' => 'assistant', 'content' => $content],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 20,
                'completion_tokens' => 10,
                'total_tokens' => 30,
            ],
        ], \JSON_THROW_ON_ERROR);
    }

    private function makeModelsResponse(): string
    {
        return json_encode([
            'object' => 'list',
            'data' => [
                ['id' => 'llama-3.3-70b', 'object' => 'model', 'owned_by' => 'vllm'],
                ['id' => 'mistral-small', 'object' => 'model', 'owned_by' => 'vllm'],
            ],
        ], \JSON_THROW_ON_ERROR);
    }

    /**
     * Build an SSE body from an array of content tokens.
     *
     * @param list<string> $tokens
     */
    private function makeSseBody(array $tokens, string $id = 'chatcmpl-stream', string $model = 'llama-3.3-70b'): string
    {
        $lines = [];
        $lastIndex = \count($tokens) - 1;

        foreach ($tokens as $i => $token) {
            $isLast = ($i === $lastIndex);
            $chunk = [
                'id' => $id,
                'object' => 'chat.completion.chunk',
                'model' => $model,
                'choices' => [
                    [
                        'index' => 0,
                        'delta' => $isLast ? [] : ['role' => 'assistant', 'content' => $token],
                        'finish_reason' => $isLast ? 'stop' : null,
                    ],
                ],
            ];
            $lines[] = 'data: '.json_encode($chunk, \JSON_THROW_ON_ERROR);
        }

        $lines[] = 'data: [DONE]';

        return implode("\n", $lines)."\n";
    }

    private function makeClient(MockHttpClient $httpClient, string $model = 'llama-3.3-70b'): OpenAiCompatibleClient
    {
        return new OpenAiCompatibleClient(
            httpClient: $httpClient,
            baseUrl: 'http://localhost:8000/v1',
            model: $model,
        );
    }

    // -------------------------------------------------------------------------
    // complete()
    // -------------------------------------------------------------------------

    public function testCompleteWithMockHttpClient(): void
    {
        $httpClient = new MockHttpClient(new MockResponse($this->makeSuccessResponse('Hello from vLLM!')));
        $client = $this->makeClient($httpClient);

        $response = $client->complete([ChatMessage::user('Hi')]);

        self::assertSame('Hello from vLLM!', $response->content);
        self::assertSame('llama-3.3-70b', $response->model);
        self::assertSame('stop', $response->finishReason);
        self::assertSame(20, $response->promptTokens);
        self::assertSame(10, $response->completionTokens);
        self::assertSame(30, $response->totalTokens());
    }

    public function testCompleteWithOptions(): void
    {
        $httpClient = new MockHttpClient(new MockResponse($this->makeSuccessResponse()));
        $client = $this->makeClient($httpClient);

        $response = $client->complete(
            [ChatMessage::user('Translate this.')],
            CompletionOptions::leichteSprache(),
        );

        self::assertSame('Test response', $response->content);
    }

    public function testCompleteWithSystemMessage(): void
    {
        $httpClient = new MockHttpClient(new MockResponse($this->makeSuccessResponse('Translated text')));
        $client = $this->makeClient($httpClient);

        $response = $client->complete([
            ChatMessage::system('You translate to Leichte Sprache.'),
            ChatMessage::user('Translate: The government passed new legislation.'),
        ]);

        self::assertSame('Translated text', $response->content);
    }

    public function testCompleteMeasuresDuration(): void
    {
        $httpClient = new MockHttpClient(new MockResponse($this->makeSuccessResponse()));
        $client = $this->makeClient($httpClient);

        $response = $client->complete([ChatMessage::user('Hi')]);

        self::assertGreaterThanOrEqual(0.0, $response->durationMs);
    }

    public function testCompleteThrowsInferenceExceptionOn400(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('{"error": "Bad request"}', ['http_code' => 400]),
        );
        $client = $this->makeClient($httpClient);

        $this->expectException(InferenceException::class);
        $client->complete([ChatMessage::user('test')]);
    }

    public function testCompleteThrowsInferenceExceptionOn500(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('Internal Server Error', ['http_code' => 500]),
        );
        $client = $this->makeClient($httpClient);

        $this->expectException(InferenceException::class);
        $client->complete([ChatMessage::user('test')]);
    }

    public function testCompleteThrowsModelNotFoundExceptionOn404(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('{"error": "model not found"}', ['http_code' => 404]),
        );
        $client = $this->makeClient($httpClient, 'missing-model');

        $this->expectException(ModelNotFoundException::class);
        $client->complete([ChatMessage::user('test')]);
    }

    public function testCompleteThrowsConnectionExceptionOnTransportError(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('', ['http_code' => 0, 'error' => 'Connection refused']),
        );
        $client = $this->makeClient($httpClient);

        $this->expectException(ConnectionException::class);
        $client->complete([ChatMessage::user('test')]);
    }

    public function testCompleteThrowsInvalidResponseExceptionOnMalformedJson(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('not-json'));
        $client = $this->makeClient($httpClient);

        $this->expectException(InvalidResponseException::class);
        $client->complete([ChatMessage::user('test')]);
    }

    public function testCompleteThrowsInvalidResponseExceptionOnMissingChoices(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse(json_encode(['id' => 'x', 'model' => 'y', 'choices' => []], \JSON_THROW_ON_ERROR)),
        );
        $client = $this->makeClient($httpClient);

        $this->expectException(InvalidResponseException::class);
        $client->complete([ChatMessage::user('test')]);
    }

    // -------------------------------------------------------------------------
    // stream()
    // -------------------------------------------------------------------------

    public function testStreamYieldsChunks(): void
    {
        // makeSseBody treats the last entry as the stop-chunk (empty delta, finish_reason=stop).
        // Content tokens must all come before the final entry.
        $tokens = ['Hello', ' world', '[stop]'];
        $httpClient = new MockHttpClient(
            new MockResponse($this->makeSseBody($tokens)),
        );
        $client = $this->makeClient($httpClient);

        $chunks = iterator_to_array($client->stream([ChatMessage::user('hi')]));

        // Expect at least the stop-chunk
        self::assertGreaterThanOrEqual(1, \count($chunks));

        // Content from the two non-stop tokens
        $collected = implode('', array_map(static fn ($c) => $c->delta, $chunks));
        self::assertSame('Hello world', $collected);
    }

    public function testStreamLastChunkIsMarked(): void
    {
        $tokens = ['Hello', ' world'];
        $httpClient = new MockHttpClient(
            new MockResponse($this->makeSseBody($tokens)),
        );
        $client = $this->makeClient($httpClient);

        $chunks = iterator_to_array($client->stream([ChatMessage::user('hi')]));
        $last = end($chunks);

        self::assertNotFalse($last);
        self::assertTrue($last->isLast);
        self::assertSame('stop', $last->finishReason);
    }

    public function testStreamThrowsModelNotFoundExceptionOn404(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('{"error": "not found"}', ['http_code' => 404]),
        );
        $client = $this->makeClient($httpClient, 'missing-model');

        $this->expectException(ModelNotFoundException::class);

        // Must consume the generator to trigger the HTTP call
        iterator_to_array($client->stream([ChatMessage::user('hi')]));
    }

    public function testStreamThrowsInferenceExceptionOn500(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('Server Error', ['http_code' => 500]),
        );
        $client = $this->makeClient($httpClient);

        $this->expectException(InferenceException::class);
        iterator_to_array($client->stream([ChatMessage::user('hi')]));
    }

    public function testStreamThrowsConnectionExceptionOnTransportError(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('', ['http_code' => 0, 'error' => 'Connection refused']),
        );
        $client = $this->makeClient($httpClient);

        $this->expectException(ConnectionException::class);
        iterator_to_array($client->stream([ChatMessage::user('hi')]));
    }

    public function testStreamIgnoresNonDataLines(): void
    {
        $body = ": keep-alive\n"
            .'data: '.json_encode([
                'id' => 'x',
                'choices' => [['delta' => ['content' => 'Hi'], 'finish_reason' => null]],
            ], \JSON_THROW_ON_ERROR)."\n"
            .'data: '.json_encode([
                'id' => 'x',
                'choices' => [['delta' => [], 'finish_reason' => 'stop']],
            ], \JSON_THROW_ON_ERROR)."\n"
            ."data: [DONE]\n";

        $httpClient = new MockHttpClient(new MockResponse($body));
        $client = $this->makeClient($httpClient);

        $chunks = iterator_to_array($client->stream([ChatMessage::user('hi')]));

        self::assertGreaterThanOrEqual(1, \count($chunks));
        self::assertSame('Hi', $chunks[0]->delta);
    }

    // -------------------------------------------------------------------------
    // listModels()
    // -------------------------------------------------------------------------

    public function testListModels(): void
    {
        $httpClient = new MockHttpClient(new MockResponse($this->makeModelsResponse()));
        $client = $this->makeClient($httpClient);

        $models = $client->listModels();

        self::assertCount(2, $models);
        self::assertSame('llama-3.3-70b', $models[0]->id);
        self::assertSame('mistral-small', $models[1]->id);
    }

    public function testListModelsThrowsOnMissingDataField(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse(json_encode(['object' => 'list'], \JSON_THROW_ON_ERROR)),
        );
        $client = $this->makeClient($httpClient);

        $this->expectException(InvalidResponseException::class);
        $client->listModels();
    }

    public function testListModelsThrowsConnectionExceptionOnTransportError(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('', ['http_code' => 0, 'error' => 'refused']),
        );
        $client = $this->makeClient($httpClient);

        $this->expectException(ConnectionException::class);
        $client->listModels();
    }

    // -------------------------------------------------------------------------
    // isHealthy()
    // -------------------------------------------------------------------------

    public function testIsHealthyReturnsTrueOn200(): void
    {
        $httpClient = new MockHttpClient(new MockResponse($this->makeModelsResponse()));
        $client = $this->makeClient($httpClient);

        self::assertTrue($client->isHealthy());
    }

    public function testIsHealthyReturnsFalseOn500(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('Internal Server Error', ['http_code' => 500]),
        );
        $client = $this->makeClient($httpClient);

        self::assertFalse($client->isHealthy());
    }

    public function testIsHealthyReturnsFalseOnTransportError(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('', ['http_code' => 0, 'error' => 'refused']),
        );
        $client = $this->makeClient($httpClient);

        self::assertFalse($client->isHealthy());
    }
}
