<?php

declare(strict_types=1);

namespace Steg\Client;

use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;
use Steg\Model\CompletionResponse;
use Steg\Model\ModelInfo;
use Steg\Model\StreamChunk;

/**
 * Central contract for LLM inference communication.
 *
 * Implementations must handle OpenAI-compatible chat/completions endpoints.
 * This interface is BC-stable from v1.0.0 onwards.
 */
interface InferenceClientInterface
{
    /**
     * Send a chat completion request and return the full response.
     *
     * @param list<ChatMessage> $messages Conversation history
     *
     * @throws \Steg\Exception\ConnectionException      When the server is unreachable
     * @throws \Steg\Exception\InferenceException       When the server returns 4xx/5xx
     * @throws \Steg\Exception\ModelNotFoundException   When the requested model is not loaded
     * @throws \Steg\Exception\InvalidResponseException When the response cannot be parsed
     */
    public function complete(array $messages, ?CompletionOptions $options = null): CompletionResponse;

    /**
     * Send a chat completion request and yield response chunks for streaming.
     *
     * @param list<ChatMessage> $messages Conversation history
     *
     * @return \Generator<int, StreamChunk, mixed, void>
     *
     * @throws \Steg\Exception\ConnectionException      When the server is unreachable
     * @throws \Steg\Exception\InferenceException       When the server returns 4xx/5xx
     * @throws \Steg\Exception\ModelNotFoundException   When the requested model is not loaded
     * @throws \Steg\Exception\InvalidResponseException When the response cannot be parsed
     */
    public function stream(array $messages, ?CompletionOptions $options = null): \Generator;

    /**
     * List all models available on the inference server.
     *
     * @return list<ModelInfo>
     *
     * @throws \Steg\Exception\ConnectionException When the server is unreachable
     * @throws \Steg\Exception\InferenceException  When the server returns an error
     */
    public function listModels(): array;

    /**
     * Check if the inference server is reachable and healthy.
     */
    public function isHealthy(): bool;
}
