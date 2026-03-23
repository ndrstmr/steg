# Configuration

## DSN Format

```
{scheme}://{host}:{port}{path}?model={model}&api_key={key}&timeout={seconds}
```

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `model`   | ✅ yes   | —       | Model ID to use for inference |
| `api_key` | ❌ no    | `EMPTY` | API key (vLLM accepts any non-empty value) |
| `timeout` | ❌ no    | `120`   | Request timeout in seconds |

### Examples

```
vllm://localhost:8000/v1?model=llama-3.3-70b-awq
vllm://localhost:8000/v1?model=llama-3.3-70b-awq&timeout=60
ollama://localhost:11434?model=llama3.2
litellm://localhost:4000/v1?model=gpt-4&api_key=sk-my-key
localai://localhost:8080/v1?model=ggml-gpt4all-j
mock://default
mock://default?response=Custom+test+response&model=my-test-model
```

## Array Config

```php
StegClientFactory::fromConfig([
    'base_url' => 'http://localhost:8000/v1',  // required
    'model'    => 'llama-3.3-70b-awq',          // required
    'api_key'  => 'EMPTY',                      // optional
    'timeout'  => 120,                          // optional, seconds
]);
```

## CompletionOptions

```php
use Steg\Model\CompletionOptions;

// Presets
CompletionOptions::default()          // temperature: 0.7, maxTokens: 4096
CompletionOptions::precise()          // temperature: 0.1 — for JSON / structured output
CompletionOptions::creative()         // temperature: 0.9 — for creative text
CompletionOptions::leichteSprache()   // temperature: 0.3 — LS-KI default

// Custom
new CompletionOptions(
    temperature: 0.5,
    maxTokens: 2048,
    topP: 0.95,
    stop: ['</answer>'],
    frequencyPenalty: 0.1,
    presencePenalty: 0.0,
);

// Fluent modification (immutable — returns new instance)
$opts = CompletionOptions::default()
    ->withTemperature(0.4)
    ->withMaxTokens(1024);
```
