<?php

declare(strict_types=1);

namespace Steg\Exception;

/**
 * Abstract base exception for all Steg errors.
 *
 * Exception hierarchy:
 *   StegException (abstract)
 *   ├── ConnectionException    — server unreachable / timeout
 *   ├── InferenceException     — LLM error (4xx/5xx from server)
 *   ├── ModelNotFoundException — requested model not available
 *   └── InvalidResponseException — response parsing failed
 */
abstract class StegException extends \RuntimeException
{
    public function __construct(
        string $message,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
