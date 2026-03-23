<?php

declare(strict_types=1);

namespace Steg\Model;

/**
 * Immutable value object bundling messages and options for a completion request.
 */
final readonly class CompletionRequest
{
    /**
     * @param list<ChatMessage> $messages
     */
    public function __construct(
        public readonly array $messages,
        public readonly CompletionOptions $options,
        public readonly string $model,
    ) {
    }

    /**
     * @param list<ChatMessage> $messages
     */
    public static function create(
        array $messages,
        string $model,
        ?CompletionOptions $options = null,
    ): self {
        return new self(
            messages: $messages,
            options: $options ?? CompletionOptions::default(),
            model: $model,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'model' => $this->model,
                'messages' => array_map(
                    static fn (ChatMessage $m) => $m->toArray(),
                    $this->messages,
                ),
            ],
            $this->options->toArray(),
        );
    }
}
