# Modern PHP 8.4 Features

## Strict Types & Type Declarations

```php
<?php

declare(strict_types=1);

namespace Steg\Model;

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
}

// Union types
function parseStatusCode(int|string $code): int
{
    return is_string($code) ? (int) $code : $code;
}

// Intersection types
interface Stringable {}
interface Countable {}

function handleCollection(Stringable&Countable $collection): void {}
```

## Enums with Methods

```php
<?php

declare(strict_types=1);

enum FinishReason: string
{
    case Stop = 'stop';
    case Length = 'length';
    case ContentFilter = 'content_filter';
    case Unknown = 'unknown';

    public function isComplete(): bool
    {
        return $this === self::Stop;
    }

    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::Unknown;
    }
}

enum HttpMethod: string
{
    case Get = 'GET';
    case Post = 'POST';

    public function hasBody(): bool
    {
        return $this === self::Post;
    }
}
```

## Readonly Properties & Classes

```php
<?php

declare(strict_types=1);

// Readonly class — all properties implicitly readonly
final readonly class ChatMessage
{
    public function __construct(
        public string $role,
        public string $content,
    ) {
        if (!\in_array($role, ['system', 'user', 'assistant'], true)) {
            throw new \InvalidArgumentException(
                \sprintf('Invalid role "%s".', $role),
            );
        }
    }

    public static function user(string $content): self
    {
        return new self('user', $content);
    }

    public static function system(string $content): self
    {
        return new self('system', $content);
    }
}

// Immutable with wither methods
final readonly class CompletionOptions
{
    public function __construct(
        public float $temperature = 0.7,
        public int $maxTokens = 4096,
    ) {
    }

    public function withTemperature(float $temperature): self
    {
        return new self(temperature: $temperature, maxTokens: $this->maxTokens);
    }
}
```

## Attributes (Metadata)

```php
<?php

declare(strict_types=1);

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsInferenceBackend
{
    public function __construct(
        public string $scheme,
        public int $defaultPort,
    ) {
    }
}

// Using attributes
#[AsInferenceBackend(scheme: 'vllm', defaultPort: 8000)]
final class VllmClient implements InferenceClientInterface
{
    // ...
}
```

## First-Class Callables

```php
<?php

declare(strict_types=1);

final class ModelInfo
{
    public static function fromApiResponse(array $data): self { /* ... */ }
}

// First-class callable syntax (PHP 8.1+)
$factory = ModelInfo::fromApiResponse(...);

// Used in array_map
$models = array_map(
    ModelInfo::fromApiResponse(...),
    $data['data'],
);
```

## Match Expressions

```php
<?php

declare(strict_types=1);

function resolveDefaultPort(string $scheme): int
{
    return match ($scheme) {
        'vllm'    => 8000,
        'ollama'  => 11434,
        'litellm' => 4000,
        'localai', 'llama' => 8080,
        default   => throw new \InvalidArgumentException(
            \sprintf('Unknown scheme "%s".', $scheme),
        ),
    };
}

function categorizeStatusCode(int $code): string
{
    return match (true) {
        $code < 400              => 'success',
        $code === 404            => 'not_found',
        $code >= 400 && $code < 500 => 'client_error',
        default                  => 'server_error',
    };
}
```

## Fibers (PHP 8.1+)

```php
<?php

declare(strict_types=1);

// Streaming response via fiber
$fiber = new \Fiber(function (): void {
    $value = \Fiber::suspend('chunk 1');
    \Fiber::suspend('chunk 2');
});

$chunk1 = $fiber->start();   // 'chunk 1'
$chunk2 = $fiber->resume();  // 'chunk 2'
```

## Property Hooks (PHP 8.4)

```php
<?php

declare(strict_types=1);

class StreamBuffer
{
    private string $raw = '';

    public string $content {
        get => trim($this->raw);
        set (string $value) {
            $this->raw .= $value;
        }
    }
}
```

## Asymmetric Visibility (PHP 8.4)

```php
<?php

declare(strict_types=1);

class ConnectionPool
{
    public private(set) int $activeConnections = 0;

    public function acquire(): void
    {
        ++$this->activeConnections;
    }
}
```

## Never Type

```php
<?php

declare(strict_types=1);

function throwNotFound(string $modelId): never
{
    throw new \Steg\Exception\ModelNotFoundException($modelId);
}
```

## Quick Reference

| Feature | PHP Version | Usage in Steg |
|---------|-------------|---------------|
| Readonly classes | 8.2+ | All Value Objects |
| Enums | 8.1+ | `FinishReason`, roles |
| First-class callables | 8.1+ | `array_map` in Factory |
| Never type | 8.1+ | Exception helpers |
| Fibers | 8.1+ | Streaming responses |
| Property hooks | 8.4 | Stream buffers |
| Asymmetric visibility | 8.4 | Internal counters |
| DNF types | 8.2+ | Complex type hints |
