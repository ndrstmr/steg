# Configuration Reference

## DSN Format

```
{scheme}://{host}:{port}{path}?model={model}&api_key={key}&timeout={seconds}
```

| Parameter | Required | Default | Description |
|-----------|----------|---------|-------------|
| `model` | ✅ yes | — | Model ID to use for inference |
| `api_key` | ❌ no | `EMPTY` | API key (vLLM accepts any non-empty value) |
| `timeout` | ❌ no | `120` | Request timeout in seconds |

### DSN Examples

```
vllm://localhost:8000/v1?model=llama-3.3-70b-awq
vllm://localhost:8000/v1?model=llama-3.3-70b-awq&timeout=60
ollama://localhost:11434?model=llama3.2
litellm://localhost:4000/v1?model=gpt-4&api_key=sk-my-key
localai://localhost:8080/v1?model=ggml-gpt4all-j
llama://localhost:8080/v1?model=model
mock://default
mock://default?response=Custom+test+response&model=my-test-model
```

### Default Ports per Scheme

| Scheme | Default Port | Default Path |
|--------|-------------|--------------|
| `vllm` | 8000 | `/v1` |
| `ollama` | 11434 | `/v1` |
| `litellm` | 4000 | `/v1` |
| `localai` | 8080 | `/v1` |
| `llama` | 8080 | `/v1` |

## Array Config

Use `StegClientFactory::fromConfig()` when configuration comes from environment variables
or a DI container:

```php
StegClientFactory::fromConfig([
    'base_url' => 'http://localhost:8000/v1',  // required
    'model'    => 'llama-3.3-70b-awq',          // required
    'api_key'  => 'EMPTY',                      // optional
    'timeout'  => 120,                          // optional, seconds
]);
```

## CompletionOptions

`CompletionOptions` is a `final readonly` value object — all modification methods
return a new instance (immutable).

### Presets

```php
use Steg\Model\CompletionOptions;

CompletionOptions::default()         // temperature: 0.7, maxTokens: 4096
CompletionOptions::precise()         // temperature: 0.1 — JSON, structured output
CompletionOptions::creative()        // temperature: 0.9 — creative text generation
CompletionOptions::leichteSprache()  // temperature: 0.3 — plain language translation
```

### Custom Options

```php
$opts = new CompletionOptions(
    temperature: 0.5,
    maxTokens: 2048,
    topP: 0.95,
    stop: ['</answer>', '<|eot_id|>'],
    frequencyPenalty: 0.1,
    presencePenalty: 0.0,
);
```

### Immutable Modification

```php
$base = CompletionOptions::default();

$modified = $base
    ->withTemperature(0.4)
    ->withMaxTokens(1024);

// $base is unchanged
```

### All Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `temperature` | `float` | `0.7` | Sampling temperature (0.0–2.0) |
| `maxTokens` | `int` | `4096` | Maximum tokens to generate |
| `topP` | `float\|null` | `null` | Nucleus sampling probability |
| `stop` | `list<string>\|null` | `null` | Stop sequences |
| `frequencyPenalty` | `float\|null` | `null` | Frequency penalty (-2.0–2.0) |
| `presencePenalty` | `float\|null` | `null` | Presence penalty (-2.0–2.0) |

## Environment Variables

Steg has no built-in ENV support in the core — use the Symfony Bundle for that:

```yaml
# config/packages/steg.yaml (with steg-bundle)
steg:
    connections:
        vllm_local:
            dsn: '%env(STEG_VLLM_DSN)%'
            timeout: 120
    default_connection: vllm_local
```

```env
STEG_VLLM_DSN=vllm://gpu-server:8000/v1?model=llama-3.3-70b-awq
```

For a pure PHP setup, read the ENV variable yourself:

```php
$steg = StegClientFactory::fromDsn($_ENV['STEG_DSN'] ?? 'mock://default');
```
