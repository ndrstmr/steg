# 🌊 Steg — The Local Inference Bridge for PHP

[![CI](https://github.com/ndrstmr/steg/actions/workflows/ci.yml/badge.svg)](https://github.com/ndrstmr/steg/actions)
[![License: EUPL-1.2](https://img.shields.io/badge/License-EUPL--1.2-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.4-8892BF.svg)](https://php.net)
[![Packagist](https://img.shields.io/packagist/v/ndrstmr/steg)](https://packagist.org/packages/ndrstmr/steg)

> A lightweight, BC-stable PHP client for OpenAI-compatible inference servers.
> Built for local-first AI in production. Zero framework lock-in.

## Quickstart

```bash
composer require ndrstmr/steg symfony/http-client
```

```php
use Steg\Factory\StegClientFactory;

$steg = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');
echo $steg->ask('Erkläre Photosynthese in Leichter Sprache.');
```

## Why Steg?

| | Steg | Symfony AI | openai-php/client |
|---|---|---|---|
| Focus | Local inference | Multi-provider ecosystem | OpenAI Cloud |
| BC-Promise | ✅ from v1.0 | ❌ experimental | ✅ |
| Core dependencies | 2 (`psr/log`, `http-contracts`) | 15+ packages | 5+ packages |
| vLLM / Ollama | ✅ first-class | ⚠️ via Generic Bridge | ❌ not officially |
| Streaming | ✅ | ✅ | ✅ |
| Tool Calling | not in scope | ✅ (Agent framework) | ✅ |
| Symfony Bundle | optional (`steg-bundle`) | integrated (`ai-bundle`) | community bundle |

Steg is purpose-built for local inference deployments and provides a BC-promise that `symfony/ai-platform` does not (yet) offer.
Ideal as a stable fallback layer in production systems.

## Supported Backends

| Backend | DSN Format | Status |
|---------|------------|--------|
| vLLM | `vllm://host:port/v1?model=name` | ✅ Full support |
| Ollama | `ollama://host:port?model=name` | ✅ Full support |
| LiteLLM | `litellm://host:port/v1?model=name` | ✅ Full support |
| LocalAI | `localai://host:port/v1?model=name` | ✅ Full support |
| llama.cpp server | `llama://host:port/v1?model=name` | ✅ Full support |
| OpenAI (Cloud) | `openai://api.openai.com/v1?model=gpt-4o&api_key=sk-...` | ⚠️ Works, not core focus |
| Mock | `mock://default` | ✅ Tests & offline dev |

> All backends share the same `OpenAiCompatibleClient` — DSN prefixes are convenience aliases
> that resolve to the correct `base_url` and default port.

## Usage

### Client creation

```php
use Steg\Factory\StegClientFactory;

// DSN (recommended)
$steg = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');
$steg = StegClientFactory::fromDsn('ollama://localhost:11434?model=llama3.2');
$steg = StegClientFactory::fromDsn('mock://default?response=Hello+World');

// Array config (e.g. from Symfony parameters)
$steg = StegClientFactory::fromConfig([
    'base_url' => 'http://localhost:8000/v1',
    'model'    => 'llama-3.3-70b-awq',
    'api_key'  => 'EMPTY',
    'timeout'  => 120,
]);
```

### Completion methods

```php
// One-shot: single user prompt
$answer = $steg->ask('What is Leichte Sprache?');

// System + user: most common chat pattern
$answer = $steg->chat(
    system: 'You translate German administrative texts into Leichte Sprache.',
    user: 'Die Bundesregierung hat neue Gesetze beschlossen.',
);

// Full message history
use Steg\Model\ChatMessage;

$answer = $steg->complete([
    ChatMessage::system('You are a helpful assistant.'),
    ChatMessage::user('What is the capital of France?'),
    ChatMessage::assistant('The capital of France is Paris.'),
    ChatMessage::user('And Germany?'),
])->content;

// Streaming
foreach ($steg->stream([ChatMessage::user('Write a poem.')]) as $chunk) {
    echo $chunk->delta;
}
```

### CompletionOptions presets

```php
use Steg\Model\CompletionOptions;

$steg->ask('Generate JSON.', CompletionOptions::precise());        // temperature 0.1
$steg->ask('Write a story.', CompletionOptions::creative());       // temperature 0.9
$steg->ask('Translate.', CompletionOptions::leichteSprache());     // temperature 0.3
$steg->ask('Anything.', CompletionOptions::default());             // temperature 0.7

// Custom (immutable — returns new instance)
$opts = CompletionOptions::default()->withTemperature(0.5)->withMaxTokens(2048);
```

### Server health and model list

```php
if ($steg->isHealthy()) {
    foreach ($steg->listModels() as $model) {
        echo $model->id.PHP_EOL;
    }
}
```

## Exception Handling

```php
use Steg\Exception\ConnectionException;
use Steg\Exception\InferenceException;
use Steg\Exception\ModelNotFoundException;
use Steg\Exception\InvalidResponseException;

try {
    $response = $steg->ask('Hello');
} catch (ConnectionException $e) {
    // Server unreachable or timeout
} catch (ModelNotFoundException $e) {
    // Model not loaded on the server
    echo 'Missing model: '.$e->getModelId();
} catch (InferenceException $e) {
    // Server returned 4xx/5xx
    echo 'HTTP '.$e->getHttpStatusCode();
} catch (InvalidResponseException $e) {
    // Response parsing failed
}
```

## Testing with MockClient

```php
use Steg\Client\MockClient;
use Steg\StegClient;

// Fixed responses, cycling
$client = new StegClient(MockClient::withResponses([
    'First response',
    'Second response',
]));

$client->ask('anything'); // → 'First response'
$client->ask('anything'); // → 'Second response'
$client->ask('anything'); // → 'First response' (loops)

// Dynamic responses via callback
$client = new StegClient(MockClient::withCallback(
    static fn (array $messages) => 'Echo: '.$messages[0]->content,
));
```

## Symfony Integration

Install the optional bundle for automatic DI configuration and a Symfony Profiler panel:

```bash
composer require ndrstmr/steg-bundle
```

```yaml
# config/packages/steg.yaml
steg:
    connections:
        vllm_local:
            dsn: '%env(STEG_VLLM_DSN)%'
            timeout: 120
    default_connection: vllm_local
```

```php
use Steg\Client\InferenceClientInterface;

final class MyService
{
    public function __construct(
        private readonly InferenceClientInterface $steg,
    ) {}
}
```

See [docs/symfony-integration.md](docs/symfony-integration.md) for full details.

## Requirements

- PHP 8.4+
- `psr/log: ^3.0`
- `symfony/http-client-contracts: ^3.0`
- `symfony/http-client: 7.4.*` *(runtime, recommended)*

## Documentation

- [Getting Started](docs/getting-started.md)
- [Configuration Reference](docs/configuration.md)
- [Supported Backends](docs/supported-backends.md)
- [Symfony Integration](docs/symfony-integration.md)

## License

Licensed under the [European Union Public Licence v1.2 (EUPL-1.2)](LICENSE).

## Origin

Steg was built in a public sector context to solve a real problem: a stable, local LLM client
for production use — without framework lock-in.

---

Built by 👾 public sector dev crew
