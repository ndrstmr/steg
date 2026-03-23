# 🌊 Steg — The local inference bridge for PHP

[![CI](https://github.com/ndrstmr/steg/actions/workflows/ci.yml/badge.svg)](https://github.com/ndrstmr/steg/actions)
[![License: EUPL-1.2](https://img.shields.io/badge/License-EUPL--1.2-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.4-8892BF.svg)](https://php.net)
[![Packagist](https://img.shields.io/packagist/v/ndrstmr/steg)](https://packagist.org/packages/ndrstmr/steg)

Lightweight, BC-stable PHP client for OpenAI-compatible local inference servers.
Works with vLLM, Ollama, LiteLLM, LocalAI and llama.cpp — no cloud required.

## Quickstart

```bash
composer require ndrstmr/steg symfony/http-client
```

```php
$steg = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');
echo $steg->ask('What is Leichte Sprache?');
```

## Supported Backends

| Backend   | DSN Scheme | Status |
|-----------|------------|--------|
| vLLM      | `vllm://`  | ✅     |
| Ollama    | `ollama://`| ✅     |
| LiteLLM   | `litellm://`| ✅    |
| LocalAI   | `localai://`| ✅    |
| llama.cpp | `llama://` | ✅     |
| Mock      | `mock://`  | ✅ (tests) |

## Usage

### DSN-based client creation

```php
use Steg\Factory\StegClientFactory;

// vLLM
$steg = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');

// Ollama
$steg = StegClientFactory::fromDsn('ollama://localhost:11434?model=llama3.2');

// LiteLLM with API key
$steg = StegClientFactory::fromDsn('litellm://localhost:4000/v1?model=gpt-4&api_key=sk-...');

// Mock for tests (no server required)
$steg = StegClientFactory::fromDsn('mock://default?response=Hello+World');
```

### Array config (e.g. from Symfony parameters)

```php
$steg = StegClientFactory::fromConfig([
    'base_url' => 'http://localhost:8000/v1',
    'model'    => 'llama-3.3-70b-awq',
    'api_key'  => 'EMPTY',   // vLLM does not require a real key
    'timeout'  => 120,
]);
```

### Completion methods

```php
// One-liner: single user prompt
$answer = $steg->ask('Explain quantum computing in simple terms.');

// System + user: most common chat pattern
$answer = $steg->chat(
    system: 'You translate German texts into Leichte Sprache.',
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

$steg->ask('Generate JSON output.', CompletionOptions::precise());       // temperature 0.1
$steg->ask('Write a short story.', CompletionOptions::creative());       // temperature 0.9
$steg->ask('Translate.', CompletionOptions::leichteSprache());           // temperature 0.3
$steg->ask('Anything.', CompletionOptions::default());                   // temperature 0.7

// Custom
$opts = CompletionOptions::default()->withTemperature(0.5)->withMaxTokens(2048);
```

### Server health and model list

```php
if ($steg->isHealthy()) {
    $models = $steg->listModels();
    foreach ($models as $model) {
        echo $model->id.PHP_EOL;
    }
}
```

## Why not Symfony AI? Why not openai-php/client?

| | Steg | symfony/ai-platform | openai-php/client |
|---|---|---|---|
| BC-Promise | ✅ from v1.0.0 | ❌ experimental | ✅ |
| Local-first focus | ✅ | ⚠️ | ❌ cloud-first |
| Framework dependency | ✅ none (core) | ❌ Symfony | ❌ none |
| vLLM / Ollama out-of-box | ✅ | ⚠️ via Generic Bridge | ❌ |
| Streaming | ✅ | ✅ | ✅ |
| License | EUPL-1.2 | MIT | MIT |

**Steg** is purpose-built for local inference server deployments and provides a BC-promise that `symfony/ai-platform` does not (yet) offer. Ideal as a stable fallback layer in production systems.

## Symfony Integration

Install the optional bundle for automatic DI configuration and a Symfony Profiler panel:

```bash
composer require ndrstmr/steg-bundle
```

See [docs/symfony-integration.md](docs/symfony-integration.md) for details.

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

$client = new StegClient(MockClient::withResponses([
    'First response',
    'Second response',
]));

$client->ask('anything'); // → 'First response'
$client->ask('anything'); // → 'Second response'
```

## Requirements

- PHP 8.4+
- `psr/log: ^3.0`
- `symfony/http-client-contracts: ^3.0`
- `symfony/http-client: 7.4.*` *(runtime, recommended)*

## License

Licensed under the [European Union Public Licence v1.2 (EUPL-1.2)](LICENSE).

---

Built by 👾 public sector dev crew
