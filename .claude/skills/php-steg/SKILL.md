---
name: php-steg
description: Use when building PHP 8.4 library code, Symfony 7.4 integrations, or Steg-specific features. Invokes strict typing, PHPStan level 9, PSR standards, and BC-stable interface design. Creates final readonly value objects, BC-stable interfaces, HttpClient integrations, PHPUnit tests, and OpenAI-compatible inference client code. Use when working with Steg core, ndrstmr/steg-bundle, Symfony DI, Contracts, or any inference server integration.
license: MIT
metadata:
  author: https://github.com/ndrstmr
  version: "1.0.0"
  domain: language
  triggers: PHP, Symfony, Steg, InferenceClient, vLLM, Ollama, PHPStan, PSR, HttpClient, value object, readonly
  role: specialist
  scope: implementation
  output-format: code
---

# PHP Pro — Steg Edition

Senior PHP 8.4 developer specialised in BC-stable library design, Symfony 7.4 contracts, and OpenAI-compatible inference client architecture.

## Core Workflow

1. **Analyze architecture** — Review PHP version (8.4), dependencies, BC constraints, and Steg design principles
2. **Design models** — Create `final readonly` value objects and DTOs, BC-stable interfaces
3. **Implement** — Write strict-typed code with PSR compliance, no framework dependencies in core
4. **Secure** — Validate inputs at system boundaries, handle exceptions through Steg hierarchy
5. **Verify** — Run all four quality checks before delivery:
   ```bash
   composer validate --strict
   vendor/bin/phpstan analyse --no-progress   # Level 9 — zero errors
   vendor/bin/phpunit                          # All tests green
   vendor/bin/php-cs-fixer check              # Zero files to fix
   ```
   Only deliver when all four pass clean.

## Reference Guide

Load detailed guidance based on context:

| Topic | Reference | Load When |
|-------|-----------|-----------|
| Modern PHP | `references/modern-php-features.md` | Readonly, enums, attributes, fibers, types |
| Symfony | `references/symfony-patterns.md` | DI, HttpClient, Contracts, Bundle patterns |
| Steg Patterns | `references/steg-patterns.md` | InferenceClient, Value Objects, Factory, DSN |
| Async PHP | `references/async-patterns.md` | Fibers, ReactPHP, streaming responses |
| Testing | `references/testing-quality.md` | PHPUnit, PHPStan, MockHttpClient, CS-Fixer |

## Constraints

### MUST DO
- `declare(strict_types=1)` in every file
- `final` on all classes (library code — no inheritance by accident)
- `readonly` on all value objects and DTOs
- Type hints on all properties, parameters, and return types
- PHPStan Level 9 before delivery — zero errors, zero ignores
- PSR-12 coding standard via PHP-CS-Fixer `@Symfony` ruleset
- BC-Promise: never change public interfaces without major version bump
- Exception hierarchy: always throw from `Steg\Exception\StegException` subtypes
- Named constructor pattern for Value Objects (`ChatMessage::user()`, `CompletionOptions::precise()`)

### MUST NOT DO
- Framework dependencies in `src/` core (only `psr/log` + `symfony/http-client-contracts`)
- `mixed` types without explicit `@var` or `@param` PHPDoc
- `@phpstan-ignore` or baseline entries to silence errors — fix the root cause
- `assert()` or inline `@var` to override PHPStan's inferred type
- Breaking changes to `InferenceClientInterface` without major version bump
- Non-final classes in library code
- Mutable value objects — use `with*()` methods returning new instances

## Code Patterns

Every implementation delivers: a typed value object or interface, an implementation class, and a test.

### Final Readonly Value Object

```php
<?php

declare(strict_types=1);

namespace Steg\Model;

final readonly class CompletionOptions
{
    public function __construct(
        public readonly float $temperature = 0.7,
        public readonly int $maxTokens = 4096,
    ) {
    }

    public static function default(): self
    {
        return new self(temperature: 0.7, maxTokens: 4096);
    }

    public function withTemperature(float $temperature): self
    {
        return new self(temperature: $temperature, maxTokens: $this->maxTokens);
    }
}
```

### BC-Stable Interface

```php
<?php

declare(strict_types=1);

namespace Steg\Client;

use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;
use Steg\Model\CompletionResponse;

/**
 * BC-stable from v1.0.0. No changes without major version bump.
 */
interface InferenceClientInterface
{
    /** @param list<ChatMessage> $messages */
    public function complete(array $messages, ?CompletionOptions $options = null): CompletionResponse;

    public function isHealthy(): bool;
}
```

### PHPUnit Test Structure

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

        self::assertSame(0.7, $original->temperature);
        self::assertSame(0.1, $modified->temperature);
    }
}
```

### Steg Exception Pattern

```php
<?php

declare(strict_types=1);

namespace Steg\Exception;

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
}
```

## Output Order

When implementing a feature, deliver in this order:
1. Interface / contract (if new)
2. Value objects / DTOs
3. Implementation class
4. Exception types (if new)
5. Tests
6. Brief explanation of BC impact

## Knowledge Reference

PHP 8.4, Symfony 7.4 (http-client, http-client-contracts, dependency-injection), PSR-3/PSR-12/PSR-18, PHPUnit 11, PHPStan 2 Level 9, PHP-CS-Fixer 3 (@Symfony ruleset), OpenAI-compatible API (chat/completions, /v1/models), vLLM, Ollama, LiteLLM, LocalAI, SSE streaming, EUPL-1.2
