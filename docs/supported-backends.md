# Supported Backends

Steg works with any server that implements the OpenAI `/v1/chat/completions` API.
All backends share the same `OpenAiCompatibleClient` — DSN prefixes are convenience
aliases for the correct `base_url` and default port.

## vLLM

High-throughput inference engine. Recommended for GPU deployments (A100, H100).

```bash
docker run --gpus all -p 8000:8000 vllm/vllm-openai \
    --model meta-llama/Llama-3.3-70B-Instruct-AWQ \
    --quantization awq
```

```php
$steg = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');
```

**Notes:**
- Health check uses `GET /health` — returns 200 when the model is loaded
- vLLM accepts any non-empty `api_key` value (use `EMPTY` or leave out)
- Streaming works out of the box

## Ollama

Easy local model management. Best for development and single-user deployments.

```bash
ollama serve
ollama pull llama3.2
```

```php
$steg = StegClientFactory::fromDsn('ollama://localhost:11434?model=llama3.2');
```

**Notes:**
- OpenAI-compatible API available at `/v1` since Ollama v0.1.24
- Health check uses `GET /api/tags` — returns available models
- Model names use Ollama's format: `llama3.2`, `qwen2.5:7b`, `mistral:latest`

## LiteLLM

Proxy and gateway that translates between 100+ providers. Useful for routing,
load balancing, and cost tracking.

```bash
litellm --model ollama/llama3.2 --port 4000
```

```php
$steg = StegClientFactory::fromDsn('litellm://localhost:4000/v1?model=my-model&api_key=sk-...');
```

**Notes:**
- Requires `api_key` if LiteLLM is configured with authentication
- Model name must match the LiteLLM router configuration

## LocalAI

Drop-in OpenAI replacement with GGUF model support. Runs on CPU and GPU.

```bash
docker run -p 8080:8080 localai/localai:latest ggml-gpt4all-j
```

```php
$steg = StegClientFactory::fromDsn('localai://localhost:8080/v1?model=ggml-gpt4all-j');
```

## llama.cpp server

Minimal HTTP server built into llama.cpp. Lightweight option for single-model deployments.

```bash
./llama-server -m model.gguf --port 8080 --host 0.0.0.0
```

```php
$steg = StegClientFactory::fromDsn('llama://localhost:8080/v1?model=model');
```

**Notes:**
- llama.cpp server implements a subset of the OpenAI API
- `listModels()` may return an empty list depending on the version

## Custom / Other

Any OpenAI-compatible server can be used via `fromConfig()`:

```php
$steg = StegClientFactory::fromConfig([
    'base_url' => 'http://my-custom-server:9000/v1',
    'model'    => 'my-custom-model',
    'timeout'  => 60,
]);
```

## Mock (Tests & Offline Development)

`MockClient` requires no running server and is safe to use in CI:

```php
// Fixed DSN (response from query param)
$steg = StegClientFactory::fromDsn('mock://default?response=Hello+World');

// Programmatic
use Steg\Client\MockClient;
use Steg\StegClient;

$steg = new StegClient(new MockClient(response: 'Hello!', model: 'mock-model'));
$steg = new StegClient(MockClient::withResponses(['First', 'Second', 'Third']));
$steg = new StegClient(MockClient::withCallback(
    static fn (array $messages) => 'Echo: '.$messages[0]->content,
));
```
