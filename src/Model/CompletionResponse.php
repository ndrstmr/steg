<?php

declare(strict_types=1);

namespace Steg\Model;

/**
 * Immutable value object representing a completed inference response.
 */
final readonly class CompletionResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $model,
        public readonly int $promptTokens,
        public readonly int $completionTokens,
        public readonly string $finishReason,
        public readonly float $durationMs,
        public readonly ?string $id = null,
    ) {
    }

    public function totalTokens(): int
    {
        return $this->promptTokens + $this->completionTokens;
    }

    /**
     * @param array<string, mixed> $data Raw OpenAI-compatible response payload
     */
    public static function fromApiResponse(array $data, float $durationMs): self
    {
        $choices = $data['choices'] ?? null;
        if (!\is_array($choices) || !isset($choices[0]) || !\is_array($choices[0])) {
            throw new \Steg\Exception\InvalidResponseException('Expected field "choices[0]" missing in inference server response.');
        }

        /** @var array<string, mixed> $choice */
        $choice = $choices[0];
        $message = $choice['message'] ?? null;
        if (!\is_array($message) || !isset($message['content']) || !\is_string($message['content'])) {
            throw new \Steg\Exception\InvalidResponseException('Expected field "choices[0].message.content" missing or invalid.');
        }

        /** @var array<string, mixed> $usage */
        $usage = \is_array($data['usage'] ?? null) ? $data['usage'] : [];

        return new self(
            content: $message['content'],
            model: \is_string($data['model'] ?? null) ? $data['model'] : 'unknown',
            promptTokens: \is_int($usage['prompt_tokens'] ?? null) ? $usage['prompt_tokens'] : 0,
            completionTokens: \is_int($usage['completion_tokens'] ?? null) ? $usage['completion_tokens'] : 0,
            finishReason: \is_string($choice['finish_reason'] ?? null) ? $choice['finish_reason'] : 'unknown',
            durationMs: $durationMs,
            id: \is_string($data['id'] ?? null) ? $data['id'] : null,
        );
    }
}
