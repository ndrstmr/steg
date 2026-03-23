<?php

declare(strict_types=1);

namespace Steg\Model;

/**
 * Immutable value object representing a single chunk from a streaming response.
 */
final readonly class StreamChunk
{
    public function __construct(
        public readonly string $delta,
        public readonly bool $isLast,
        public readonly ?string $finishReason = null,
        public readonly ?string $id = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data Raw SSE data object from streaming response
     */
    public static function fromSseData(array $data): self
    {
        $choices = $data['choices'] ?? null;
        if (!\is_array($choices) || !isset($choices[0]) || !\is_array($choices[0])) {
            throw new \Steg\Exception\InvalidResponseException('Expected field "choices[0]" missing in stream chunk.');
        }

        /** @var array<string, mixed> $choice */
        $choice = $choices[0];

        $delta = $choice['delta'] ?? [];
        $content = \is_array($delta) && isset($delta['content']) && \is_string($delta['content'])
            ? $delta['content']
            : '';

        $finishReason = \is_string($choice['finish_reason'] ?? null) ? $choice['finish_reason'] : null;

        return new self(
            delta: $content,
            isLast: null !== $finishReason,
            finishReason: $finishReason,
            id: \is_string($data['id'] ?? null) ? $data['id'] : null,
        );
    }

    public function isEmpty(): bool
    {
        return '' === $this->delta;
    }
}
