# Steg-Specific Patterns

## InferenceClientInterface — The Central Contract

BC-stable from v1.0.0. Never change without major version bump.

```php
<?php

declare(strict_types=1);

namespace Steg\Client;

interface InferenceClientInterface
{
    /** @param list<ChatMessage> $messages */
    public function complete(array $messages, ?CompletionOptions $options = null): CompletionResponse;

    /** @return \Generator<int, StreamChunk, mixed, void> */
    public function stream(array $messages, ?CompletionOptions $options = null): \Generator;

    /** @return list<ModelInfo> */
    public function listModels(): array;

    public function isHealthy(): bool;
}
```

## Value Object Checklist

Every Value Object in `Steg\Model\` must be:
- `final readonly class`
- Named constructors for common cases (`::user()`, `::system()`, `::default()`, `::precise()`)
- Immutable `with*()` methods returning new instances
- `toArray()` for API serialization where needed
- `fromApiResponse()` static factory for deserialization

```php
<?php

declare(strict_types=1);

namespace Steg\Model;

final readonly class ChatMessage
{
    public function __construct(
        public readonly string $role,
        public readonly string $content,
    ) {
    }

    public static function user(string $content): self   { return new self('user', $content); }
    public static function system(string $content): self { return new self('system', $content); }
    public static function assistant(string $content): self { return new self('assistant', $content); }

    /** @return array{role: string, content: string} */
    public function toArray(): array
    {
        return ['role' => $this->role, 'content' => $this->content];
    }
}
```

## CompletionOptions Presets

Always provide domain-specific named presets — don't force callers to know raw numbers.

```php
CompletionOptions::default()         // temperature: 0.7 — general purpose
CompletionOptions::precise()         // temperature: 0.1 — JSON / structured output
CompletionOptions::creative()        // temperature: 0.9 — creative text
CompletionOptions::leichteSprache()  // temperature: 0.3 — LS-KI default
```

## Exception Hierarchy

Always throw a subtype of `StegException`. Never throw generic `\RuntimeException`.

```
StegException (abstract, extends \RuntimeException)
├── ConnectionException    — server unreachable / timeout
│     ::unreachable(string $baseUrl, \Throwable $prev)
│     ::timeout(string $baseUrl, int $timeoutSeconds)
├── InferenceException     — 4xx/5xx from server
│     ::fromHttpResponse(int $statusCode, string $body)
├── ModelNotFoundException — model not loaded
│     ::forModel(string $modelId)
└── InvalidResponseException — parsing failed
      ::malformedJson(string $raw, \Throwable $prev)
      ::missingField(string $fieldPath)
      ::unexpectedFormat(string $description)
```

## DSN Format

```
{scheme}://{host}:{port}{path}?model={model}&api_key={key}&timeout={seconds}

vllm://localhost:8000/v1?model=llama-3.3-70b-awq
ollama://localhost:11434?model=llama3.2
litellm://localhost:4000/v1?model=gpt-4&api_key=sk-...
localai://localhost:8080/v1?model=ggml-gpt4all-j
llama://localhost:8080/v1?model=model
mock://default
mock://default?response=Hello+World&model=test-model
```

## StegClientFactory Pattern

```php
// DSN — recommended for ENV-driven config
$steg = StegClientFactory::fromDsn($_ENV['STEG_DSN']);

// Array config — for programmatic setup
$steg = StegClientFactory::fromConfig([
    'base_url' => 'http://localhost:8000/v1',
    'model'    => 'llama-3.3-70b-awq',
    'api_key'  => 'EMPTY',
    'timeout'  => 120,
]);

// Mock — always for tests
$steg = StegClientFactory::fromDsn('mock://default');
```

## StegClient Façade Methods

```php
$steg->ask(string $prompt, ?CompletionOptions $options): string
$steg->chat(string $system, string $user, ?CompletionOptions $options): string
$steg->complete(array $messages, ?CompletionOptions $options): CompletionResponse
$steg->stream(array $messages, ?CompletionOptions $options): \Generator<StreamChunk>
$steg->listModels(): list<ModelInfo>
$steg->isHealthy(): bool
$steg->getClient(): InferenceClientInterface
```

## MockClient for Tests

```php
<?php

declare(strict_types=1);

// Single fixed response
$client = new MockClient(response: 'Fixed answer.');

// Cycling responses
$client = MockClient::withResponses(['First', 'Second', 'Third']);

// Unhealthy server simulation
$client = MockClient::unhealthy();

// Assert call count
self::assertSame(2, $client->getCallCount());
$client->reset();
```

## Integration Test with MockHttpClient

```php
<?php

declare(strict_types=1);

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

$httpClient = new MockHttpClient(
    new MockResponse(json_encode([
        'choices' => [['message' => ['content' => 'Test'], 'finish_reason' => 'stop']],
        'model'   => 'llama-3.3-70b',
        'usage'   => ['prompt_tokens' => 10, 'completion_tokens' => 5],
    ], \JSON_THROW_ON_ERROR)),
);

$client   = new OpenAiCompatibleClient($httpClient, 'http://localhost:8000/v1', 'llama-3.3-70b');
$response = $client->complete([ChatMessage::user('Hello')]);

self::assertSame('Test', $response->content);
```

## BC Rules (v1.0.0+)

| Action | Allowed? |
|--------|----------|
| Add optional parameter to interface method | ❌ Breaking |
| Add new method to interface | ❌ Breaking |
| Add new class to src/ | ✅ |
| Add new static factory to Value Object | ✅ |
| Change exception message text | ✅ |
| Change exception type thrown | ❌ Breaking |
| Remove public method | ❌ Breaking |
| Change return type | ❌ Breaking |
