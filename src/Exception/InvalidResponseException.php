<?php

declare(strict_types=1);

namespace Steg\Exception;

/**
 * Thrown when the inference server response cannot be parsed.
 */
final class InvalidResponseException extends StegException
{
    public static function malformedJson(string $rawBody, \Throwable $previous): self
    {
        return new self(
            \sprintf('Failed to parse inference server response as JSON: %s', $previous->getMessage()),
            0,
            $previous,
        );
    }

    public static function missingField(string $fieldPath): self
    {
        return new self(
            \sprintf('Expected field "%s" missing in inference server response.', $fieldPath),
        );
    }

    public static function unexpectedFormat(string $description): self
    {
        return new self(
            \sprintf('Unexpected response format: %s', $description),
        );
    }
}
