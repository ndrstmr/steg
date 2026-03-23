<?php

declare(strict_types=1);

namespace Steg\Model;

/**
 * Immutable value object representing a model available on an inference server.
 */
final readonly class ModelInfo
{
    public function __construct(
        public readonly string $id,
        public readonly string $ownedBy,
        public readonly ?\DateTimeImmutable $created = null,
        public readonly ?string $name = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data Raw entry from /v1/models response
     */
    public static function fromApiResponse(array $data): self
    {
        if (!isset($data['id']) || !\is_string($data['id'])) {
            throw new \Steg\Exception\InvalidResponseException('Expected field "id" missing or not a string in model info.');
        }

        $created = null;
        if (isset($data['created']) && \is_int($data['created'])) {
            $created = (new \DateTimeImmutable())->setTimestamp($data['created']);
        }

        return new self(
            id: $data['id'],
            ownedBy: \is_string($data['owned_by'] ?? null) ? $data['owned_by'] : 'unknown',
            created: $created,
            name: \is_string($data['name'] ?? null) ? $data['name'] : null,
        );
    }
}
