<?php

declare(strict_types=1);

namespace Steg\Client;

use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;
use Steg\Model\CompletionResponse;
use Steg\Model\ModelInfo;
use Steg\Model\StreamChunk;

/**
 * Deterministic mock client for tests and Tier-1 development (no server required).
 *
 * Configure fixed responses via the constructor. Useful for:
 *   - Unit tests that should not depend on a running inference server
 *   - Local development without GPU access
 *   - CI pipelines
 *
 * Usage:
 *   $client = new MockClient(response: 'Hello from mock!');
 *   $client = MockClient::withResponses(['First response', 'Second response']);
 */
final class MockClient implements InferenceClientInterface
{
    private int $callCount = 0;

    /** @var list<string> */
    private array $responses;

    /**
     * @param list<string>|null $responses Cycle through these responses in order (loops)
     */
    public function __construct(
        private readonly string $response = 'Mock response.',
        private readonly string $model = 'mock-model',
        private readonly bool $healthy = true,
        ?array $responses = null,
    ) {
        $this->responses = $responses ?? [$response];
    }

    /**
     * @param list<string> $responses
     */
    public static function withResponses(array $responses, string $model = 'mock-model'): self
    {
        return new self(
            response: $responses[0] ?? 'Mock response.',
            model: $model,
            responses: $responses,
        );
    }

    public static function unhealthy(): self
    {
        return new self(healthy: false);
    }

    public function complete(array $messages, ?CompletionOptions $options = null): CompletionResponse
    {
        $content = $this->nextResponse();
        ++$this->callCount;

        return new CompletionResponse(
            content: $content,
            model: $this->model,
            promptTokens: $this->estimateTokens($messages),
            completionTokens: (int) (\strlen($content) / 4),
            finishReason: 'stop',
            durationMs: 0.0,
            id: \sprintf('mock-%d', $this->callCount),
        );
    }

    public function stream(array $messages, ?CompletionOptions $options = null): \Generator
    {
        $content = $this->nextResponse();
        ++$this->callCount;

        $words = explode(' ', $content);
        $lastIndex = \count($words) - 1;

        foreach ($words as $i => $word) {
            $delta = 0 === $i ? $word : ' '.$word;
            $isLast = $i === $lastIndex;

            yield new StreamChunk(
                delta: $delta,
                isLast: $isLast,
                finishReason: $isLast ? 'stop' : null,
                id: \sprintf('mock-%d', $this->callCount),
            );
        }
    }

    public function listModels(): array
    {
        return [
            new ModelInfo(
                id: $this->model,
                ownedBy: 'mock',
                created: new \DateTimeImmutable('2024-01-01'),
                name: 'Mock Model',
            ),
        ];
    }

    public function isHealthy(): bool
    {
        return $this->healthy;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }

    public function reset(): void
    {
        $this->callCount = 0;
    }

    private function nextResponse(): string
    {
        if ([] === $this->responses) {
            return $this->response;
        }

        return $this->responses[$this->callCount % \count($this->responses)];
    }

    /**
     * @param list<ChatMessage> $messages
     */
    private function estimateTokens(array $messages): int
    {
        $total = 0;
        foreach ($messages as $message) {
            $total += (int) (\strlen($message->content) / 4);
        }

        return $total;
    }
}
