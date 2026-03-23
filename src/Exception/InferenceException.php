<?php

declare(strict_types=1);

namespace Steg\Exception;

/**
 * Thrown when the inference server returns an error response (4xx/5xx).
 */
final class InferenceException extends StegException
{
    public function __construct(
        string $message,
        private readonly int $httpStatusCode,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromHttpResponse(int $statusCode, string $responseBody): self
    {
        return new self(
            \sprintf('Inference server returned HTTP %d: %s', $statusCode, $responseBody),
            $statusCode,
        );
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}
