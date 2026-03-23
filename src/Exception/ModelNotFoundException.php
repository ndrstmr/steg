<?php

declare(strict_types=1);

namespace Steg\Exception;

/**
 * Thrown when the requested model is not available on the inference server.
 */
final class ModelNotFoundException extends StegException
{
    public function __construct(
        private readonly string $modelId,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            \sprintf('Model "%s" is not available on the inference server.', $modelId),
            0,
            $previous,
        );
    }

    public static function forModel(string $modelId, ?\Throwable $previous = null): self
    {
        return new self($modelId, $previous);
    }

    public function getModelId(): string
    {
        return $this->modelId;
    }
}
