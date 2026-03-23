<?php

declare(strict_types=1);

namespace Steg\Model;

/**
 * Immutable value object representing a single message in a conversation.
 */
final readonly class ChatMessage
{
    public function __construct(
        public readonly string $role,
        public readonly string $content,
    ) {
        if (!\in_array($role, ['system', 'user', 'assistant'], true)) {
            throw new \InvalidArgumentException(\sprintf('Invalid role "%s". Must be one of: system, user, assistant.', $role));
        }
    }

    public static function system(string $content): self
    {
        return new self('system', $content);
    }

    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    public static function assistant(string $content): self
    {
        return new self('assistant', $content);
    }

    /**
     * @return array{role: string, content: string}
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
        ];
    }
}
