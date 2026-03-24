# Testing & Quality — Steg Standards

## The Four Checks (must all pass before delivery)

```bash
composer validate --strict                    # composer.json valid
vendor/bin/phpstan analyse --no-progress      # Level 9, zero errors
vendor/bin/phpunit                            # All tests green
vendor/bin/php-cs-fixer check                 # Zero files to fix
```

## PHPUnit — Unit Test Structure

```php
<?php

declare(strict_types=1);

namespace Steg\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Steg\Model\CompletionOptions;

final class CompletionOptionsTest extends TestCase
{
    public function testDefaultPreset(): void
    {
        $opts = CompletionOptions::default();

        self::assertSame(0.7, $opts->temperature);
        self::assertSame(4096, $opts->maxTokens);
    }

    public function testWithTemperatureReturnsNewInstance(): void
    {
        $original = CompletionOptions::default();
        $modified = $original->withTemperature(0.1);

        // Original unchanged (readonly)
        self::assertSame(0.7, $original->temperature);
        self::assertSame(0.1, $modified->temperature);
        // Other properties preserved
        self::assertSame($original->maxTokens, $modified->maxTokens);
    }

    /**
     * @dataProvider provideInvalidTemperatures
     */
    public function testInvalidTemperatureThrows(float $temperature): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new CompletionOptions(temperature: $temperature);
    }

    /** @return array<string, array{float}> */
    public static function provideInvalidTemperatures(): array
    {
        return [
            'negative'      => [-0.1],
            'above maximum' => [2.1],
        ];
    }
}
```

## PHPUnit — MockClient Pattern

```php
<?php

declare(strict_types=1);

namespace Steg\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use Steg\Client\MockClient;
use Steg\Model\ChatMessage;

final class MockClientTest extends TestCase
{
    public function testCyclesThroughResponses(): void
    {
        $client = MockClient::withResponses(['First', 'Second', 'Third']);

        self::assertSame('First',  $client->complete([ChatMessage::user('q')])->content);
        self::assertSame('Second', $client->complete([ChatMessage::user('q')])->content);
        self::assertSame('Third',  $client->complete([ChatMessage::user('q')])->content);
        self::assertSame('First',  $client->complete([ChatMessage::user('q')])->content); // loops
    }

    public function testStreamYieldsDeltas(): void
    {
        $client = new MockClient(response: 'Hello World');
        $chunks = iterator_to_array($client->stream([ChatMessage::user('hi')]));

        $collected = implode('', array_map(static fn ($c) => $c->delta, $chunks));
        self::assertSame('Hello World', $collected);
        self::assertTrue($chunks[array_key_last($chunks)]->isLast);
    }
}
```

## Integration Tests — MockHttpClient

```php
<?php

declare(strict_types=1);

namespace Steg\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Steg\Client\OpenAiCompatibleClient;
use Steg\Exception\InferenceException;
use Steg\Model\ChatMessage;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenAiCompatibleClientTest extends TestCase
{
    private function successResponse(string $content = 'Test'): MockResponse
    {
        $body = json_encode([
            'model'   => 'llama-3.3-70b',
            'choices' => [['message' => ['content' => $content], 'finish_reason' => 'stop']],
            'usage'   => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ], \JSON_THROW_ON_ERROR);

        return new MockResponse(false !== $body ? $body : '');
    }

    public function testCompleteReturnsContent(): void
    {
        $client = new OpenAiCompatibleClient(
            httpClient: new MockHttpClient($this->successResponse('Hello!')),
            baseUrl: 'http://localhost:8000/v1',
            model: 'llama-3.3-70b',
        );

        self::assertSame('Hello!', $client->complete([ChatMessage::user('Hi')])->content);
    }

    public function testThrowsInferenceExceptionOn500(): void
    {
        $client = new OpenAiCompatibleClient(
            httpClient: new MockHttpClient(new MockResponse('Error', ['http_code' => 500])),
            baseUrl: 'http://localhost:8000/v1',
            model: 'llama-3.3-70b',
        );

        $this->expectException(InferenceException::class);
        $client->complete([ChatMessage::user('test')]);
    }
}
```

## PHPStan Configuration

```neon
# phpstan.neon
includes:
    - vendor/phpstan/phpstan-strict-rules/rules.neon

parameters:
    level: 9
    paths:
        - src
        - tests
    phpVersion: 80400
    treatPhpDocTypesAsCertain: false
    ignoreErrors: []   # Never add entries here — fix the root cause
```

### Common PHPStan Level 9 Fixes

```php
// ❌ PHPStan: Cannot access offset 0 on mixed
$choice = $data['choices'][0];

// ✅ Fix: check type first
$choices = $data['choices'] ?? null;
if (!\is_array($choices) || !isset($choices[0]) || !\is_array($choices[0])) {
    throw InvalidResponseException::missingField('choices[0]');
}
/** @var array<string, mixed> $choice */
$choice = $choices[0];

// ❌ PHPStan: Short ternary not allowed
$result = $value ?: 'default';

// ✅ Fix: null coalesce or explicit ternary
$result = $value ?? 'default';
$result = '' !== $value ? $value : 'default';
```

## PHP-CS-Fixer — Symfony Ruleset

```php
// .php-cs-fixer.dist.php
return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony'       => true,
        '@Symfony:risky' => true,
        'declare_strict_types'       => true,
        'native_function_invocation' => ['include' => ['@compiler_optimized'], 'scope' => 'namespaced'],
        'ordered_imports'            => ['sort_algorithm' => 'alpha'],
        'strict_comparison'          => true,
        'strict_param'               => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder)
;
```

### Common CS-Fixer Auto-Fixes

| Before | After (Symfony ruleset) |
|--------|------------------------|
| `fn ($c) =>` | `static fn ($c) =>` (when no `$this`) |
| `\iterator_to_array(` | `iterator_to_array(` |
| `\implode(` | `implode(` |
| `$foo ?: ''` | Flagged — use null coalesce |
| Empty `{}` constructor | `{\n}` formatted |

## Test Directory Structure

```
tests/
├── Unit/
│   ├── Model/          # Value Object tests — no IO
│   ├── Client/         # MockClient tests — no IO
│   └── Factory/        # Factory with mock HttpClientInterface
└── Integration/        # OpenAiCompatibleClient + MockHttpClient
```

**Rule:** Unit tests must never make real HTTP calls. Use `MockClient` or `MockHttpClient`.

## Quick Reference

| Tool | Config | Run |
|------|--------|-----|
| PHPUnit 11 | `phpunit.xml.dist` | `vendor/bin/phpunit` |
| PHPStan 2 | `phpstan.neon` | `vendor/bin/phpstan analyse --no-progress` |
| PHP-CS-Fixer 3 | `.php-cs-fixer.dist.php` | `vendor/bin/php-cs-fixer check` |
| Composer | `composer.json` | `composer validate --strict` |
