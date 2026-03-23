# Supported Backends

Steg works with any server that implements the OpenAI `/v1/chat/completions` API.

## vLLM

High-throughput inference engine. Recommended for A100/H100 deployments.

```bash
# Start vLLM
docker run --gpus all -p 8000:8000 vllm/vllm-openai \
    --model meta-llama/Llama-3.3-70B-Instruct-AWQ \
    --quantization awq
```

```php
$steg = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');
```

## Ollama

Easy local model management. Run models with a single command.

```bash
ollama serve
ollama pull llama3.2
```

```php
$steg = StegClientFactory::fromDsn('ollama://localhost:11434?model=llama3.2');
```

> **Note:** Ollama's OpenAI-compatible API is available at `/v1` since v0.1.24.

## LiteLLM

Proxy / gateway that translates between providers.

```php
$steg = StegClientFactory::fromDsn('litellm://localhost:4000/v1?model=my-model&api_key=sk-...');
```

## LocalAI

Drop-in OpenAI replacement with GGUF model support.

```php
$steg = StegClientFactory::fromDsn('localai://localhost:8080/v1?model=ggml-gpt4all-j');
```

## llama.cpp server

Minimal HTTP server built into llama.cpp.

```bash
./llama-server -m model.gguf --port 8080
```

```php
$steg = StegClientFactory::fromDsn('llama://localhost:8080/v1?model=model');
```

## Custom / Other

Any OpenAI-compatible server can be used via `fromConfig()`:

```php
$steg = StegClientFactory::fromConfig([
    'base_url' => 'http://my-custom-server:9000/v1',
    'model'    => 'my-custom-model',
]);
```
