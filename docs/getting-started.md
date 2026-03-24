# Getting Started with Steg

## Requirements

- PHP 8.4+
- A running OpenAI-compatible inference server (vLLM, Ollama, LiteLLM, LocalAI, llama.cpp)

## Installation

```bash
composer require ndrstmr/steg symfony/http-client
```

`symfony/http-client` is the recommended HTTP implementation. If you bring your own
`HttpClientInterface`, it is not required.

## Create your first client

### Option A: DSN (recommended)

The DSN format is the quickest way to configure a connection:

```php
use Steg\Factory\StegClientFactory;

// vLLM
$steg = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');

// Ollama
$steg = StegClientFactory::fromDsn('ollama://localhost:11434?model=llama3.2');

// Mock for tests (no server required)
$steg = StegClientFactory::fromDsn('mock://default');
```

### Option B: Array config

Use this when configuration comes from environment variables or a DI container:

```php
$steg = StegClientFactory::fromConfig([
    'base_url' => 'http://localhost:8000/v1',  // required
    'model'    => 'llama-3.3-70b-awq',          // required
    'api_key'  => 'EMPTY',                      // optional, default: 'EMPTY'
    'timeout'  => 120,                          // optional, default: 120
]);
```

## Send your first request

```php
// Simplest possible call
echo $steg->ask('What is Leichte Sprache?');

// System + user (recommended for production)
echo $steg->chat(
    system: 'You translate German administrative texts into Leichte Sprache.',
    user: 'Die Bundesregierung hat neue Gesetze beschlossen.',
);

// Full message history
use Steg\Model\ChatMessage;

$response = $steg->complete([
    ChatMessage::system('You are a helpful assistant.'),
    ChatMessage::user('What is the capital of France?'),
    ChatMessage::assistant('Paris.'),
    ChatMessage::user('And Germany?'),
]);
echo $response->content;
echo 'Tokens used: '.($response->promptTokens + $response->completionTokens);
```

## Streaming

```php
foreach ($steg->stream([ChatMessage::user('Write a short poem.')]) as $chunk) {
    echo $chunk->delta;
    flush();

    if ($chunk->isLast) {
        echo PHP_EOL.'[done, finish reason: '.$chunk->finishReason.']'.PHP_EOL;
    }
}
```

## Check server health

```php
if (! $steg->isHealthy()) {
    throw new \RuntimeException('Inference server is not reachable.');
}

// List available models
foreach ($steg->listModels() as $model) {
    echo $model->id.PHP_EOL;
}
```

## Error handling

```php
use Steg\Exception\ConnectionException;
use Steg\Exception\InferenceException;
use Steg\Exception\ModelNotFoundException;
use Steg\Exception\InvalidResponseException;
use Steg\Exception\StegException;

try {
    $response = $steg->ask('Hello');
} catch (ConnectionException $e) {
    // Server unreachable, timeout, or transport error
    error_log('Cannot reach inference server: '.$e->getMessage());
} catch (ModelNotFoundException $e) {
    // HTTP 404 — model not loaded
    error_log('Model not available: '.$e->getModelId());
} catch (InferenceException $e) {
    // HTTP 4xx/5xx from the server
    error_log('Inference error (HTTP '.$e->getHttpStatusCode().'): '.$e->getMessage());
} catch (InvalidResponseException $e) {
    // Response could not be parsed
    error_log('Unexpected server response: '.$e->getMessage());
} catch (StegException $e) {
    // Catch-all for all Steg exceptions
}
```

## Testing without a GPU

Use `MockClient` for tests and local development without a running inference server:

```php
use Steg\Client\MockClient;
use Steg\StegClient;

// Fixed responses, cycling
$steg = new StegClient(MockClient::withResponses([
    'Photosynthese ist, wenn Pflanzen Licht in Energie umwandeln.',
    'Das Chlorophyll ist der grüne Farbstoff der Pflanzen.',
]));

// Dynamic responses
$steg = new StegClient(MockClient::withCallback(
    static fn (array $messages) => 'Antwort auf: '.$messages[array_key_last($messages)]->content,
));
```

## Next steps

- [Configuration Reference](configuration.md) — all DSN parameters and CompletionOptions
- [Supported Backends](supported-backends.md) — backend-specific setup and tips
- [Symfony Integration](symfony-integration.md) — DI auto-configuration via steg-bundle
