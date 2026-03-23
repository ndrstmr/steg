<?php

declare(strict_types=1);

namespace Steg;

use Steg\Client\InferenceClientInterface;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;
use Steg\Model\CompletionResponse;
use Steg\Model\ModelInfo;
use Steg\Model\StreamChunk;

/**
 * Convenience façade over InferenceClientInterface.
 *
 * Provides one-liner methods for the most common use cases.
 * For full control, use the underlying client directly via getClient().
 *
 * Quick start:
 *   $steg = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');
 *   echo $steg->ask('What is the capital of Germany?');
 */
final class StegClient
{
    public function __construct(
        private readonly InferenceClientInterface $client,
    ) {
    }

    /**
     * One-shot completion: send a single user prompt and get the text response.
     */
    public function ask(string $prompt, ?CompletionOptions $options = null): string
    {
        return $this->complete([ChatMessage::user($prompt)], $options)->content;
    }

    /**
     * System + user message: the most common chat pattern.
     */
    public function chat(string $system, string $user, ?CompletionOptions $options = null): string
    {
        return $this->complete([
            ChatMessage::system($system),
            ChatMessage::user($user),
        ], $options)->content;
    }

    /**
     * Full completion with arbitrary message history.
     *
     * @param list<ChatMessage> $messages
     */
    public function complete(array $messages, ?CompletionOptions $options = null): CompletionResponse
    {
        return $this->client->complete($messages, $options);
    }

    /**
     * Streaming completion — yields StreamChunk objects.
     *
     * @param list<ChatMessage> $messages
     *
     * @return \Generator<int, StreamChunk, mixed, void>
     */
    public function stream(array $messages, ?CompletionOptions $options = null): \Generator
    {
        return $this->client->stream($messages, $options);
    }

    /**
     * List all models available on the connected inference server.
     *
     * @return list<ModelInfo>
     */
    public function listModels(): array
    {
        return $this->client->listModels();
    }

    /**
     * Check if the inference server is reachable.
     */
    public function isHealthy(): bool
    {
        return $this->client->isHealthy();
    }

    /**
     * Access the underlying client for advanced usage.
     */
    public function getClient(): InferenceClientInterface
    {
        return $this->client;
    }
}
