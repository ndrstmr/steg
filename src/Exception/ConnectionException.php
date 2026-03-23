<?php

declare(strict_types=1);

namespace Steg\Exception;

/**
 * Thrown when the inference server is unreachable or a request times out.
 */
final class ConnectionException extends StegException
{
    public static function unreachable(string $baseUrl, \Throwable $previous): self
    {
        return new self(
            \sprintf('Inference server unreachable at "%s": %s', $baseUrl, $previous->getMessage()),
            0,
            $previous,
        );
    }

    public static function timeout(string $baseUrl, int $timeoutSeconds): self
    {
        return new self(
            \sprintf('Request to "%s" timed out after %d seconds.', $baseUrl, $timeoutSeconds),
        );
    }
}
