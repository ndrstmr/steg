# Async PHP Patterns — Steg Context

Steg uses two async mechanisms: **Symfony HttpClient streaming** (primary)
and **PHP Fibers** (native, for advanced use cases). Swoole is out of scope.

## Symfony HttpClient — SSE Streaming

The primary streaming pattern in Steg core.

```php
<?php

declare(strict_types=1);

public function stream(array $messages, ?CompletionOptions $options = null): \Generator
{
    try {
        $response = $this->httpClient->request('POST', $this->baseUrl.'/chat/completions', [
            'headers' => $this->buildHeaders(),
            'json'    => array_merge($this->buildPayload($messages, $options), ['stream' => true]),
            'buffer'  => false,
            'timeout' => $this->timeout,
        ]);

        foreach ($this->httpClient->stream($response) as $chunk) {
            if ($chunk->isLast()) {
                break;
            }

            foreach (explode("\n", trim($chunk->getContent())) as $line) {
                if (!str_starts_with($line, 'data: ')) {
                    continue;
                }

                $json = substr($line, 6);
                if ('[DONE]' === $json) {
                    return;
                }

                $data        = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
                $streamChunk = StreamChunk::fromSseData($data);

                yield $streamChunk;

                if ($streamChunk->isLast) {
                    return;
                }
            }
        }
    } catch (TransportExceptionInterface $e) {
        throw ConnectionException::unreachable($this->baseUrl, $e);
    }
}
```

## Consuming a Stream

```php
<?php

declare(strict_types=1);

$steg   = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');
$buffer = '';

foreach ($steg->stream([ChatMessage::user('Write a poem.')]) as $chunk) {
    echo $chunk->delta;
    $buffer .= $chunk->delta;

    if ($chunk->isLast) {
        break;
    }
}

echo PHP_EOL.'Finish reason: '.$chunk->finishReason.PHP_EOL;
```

## PHP Fibers (PHP 8.1+)

Native coroutines — useful for managing multiple concurrent inference requests
without an event loop dependency.

```php
<?php

declare(strict_types=1);

// Run two inference requests concurrently via fibers
$fiber1 = new \Fiber(function () use ($steg): string {
    return $steg->ask('Translate: Bundesregierung');
});

$fiber2 = new \Fiber(function () use ($steg): string {
    return $steg->ask('Translate: Gesundheitsministerium');
});

$result1 = $fiber1->start();
$result2 = $fiber2->start();

// Both fibers are now running; resume as needed
```

## ReactPHP (Optional, via Suggest)

For event-loop-based applications. Not a core dependency of Steg.

```php
<?php

declare(strict_types=1);

use React\EventLoop\Loop;
use React\Promise\Promise;

// Promise-based wrapper around Steg
function asyncAsk(StegClient $steg, string $prompt): Promise
{
    return new Promise(function (callable $resolve) use ($steg, $prompt): void {
        $result = $steg->ask($prompt);
        $resolve($result);
    });
}

$loop = Loop::get();

asyncAsk($steg, 'Hello')
    ->then(fn(string $answer) => print $answer.PHP_EOL);

$loop->run();
```

## StreamChunk Value Object

```php
<?php

declare(strict_types=1);

namespace Steg\Model;

final readonly class StreamChunk
{
    public function __construct(
        public readonly string $delta,
        public readonly bool $isLast,
        public readonly ?string $finishReason = null,
        public readonly ?string $id = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return '' === $this->delta;
    }

    /** @param array<string, mixed> $data */
    public static function fromSseData(array $data): self
    {
        // ... parses OpenAI SSE format
    }
}
```

## Quick Reference

| Technology | Use case | Dependency |
|------------|----------|------------|
| Symfony HttpClient stream | SSE streaming in Steg core | `symfony/http-client-contracts` |
| PHP Fibers | Concurrent requests, no extension needed | Built into PHP 8.1+ |
| ReactPHP | Event-loop integration | `react/event-loop` (suggest) |
| Swoole | High-perf server (not used in Steg) | — |
