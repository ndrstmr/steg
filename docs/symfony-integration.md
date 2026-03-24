# Symfony Integration

## Option A: ndrstmr/steg-bundle (recommended)

The optional bundle provides automatic DI configuration, multi-connection support,
and a Symfony Profiler panel.

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
        ollama_dev:
            dsn: 'ollama://localhost:11434?model=qwen2.5:7b'
            timeout: 60
        mock:
            dsn: 'mock://default'
    default_connection: vllm_local
```

```php
// Inject via InferenceClientInterface (default connection)
use Steg\Client\InferenceClientInterface;

final class TranslationService
{
    public function __construct(
        private readonly InferenceClientInterface $steg,
    ) {}
}

// Named connection via #[Autowire]
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class MultiModelService
{
    public function __construct(
        #[Autowire(service: 'steg.client.vllm_local')]
        private readonly InferenceClientInterface $vllm,

        #[Autowire(service: 'steg.client.ollama_dev')]
        private readonly InferenceClientInterface $ollama,
    ) {}
}
```

See the [steg-bundle repository](https://github.com/ndrstmr/steg-bundle) for the full documentation.

## Option B: Manual service wiring

Without the bundle, register the client manually in `config/services.yaml`:

```yaml
services:
    Steg\StegClient:
        factory: ['Steg\Factory\StegClientFactory', 'fromDsn']
        arguments:
            - '%env(STEG_DSN)%'

    Steg\Client\InferenceClientInterface:
        alias: Steg\StegClient
```

Or with explicit config:

```yaml
services:
    Steg\StegClient:
        factory: ['Steg\Factory\StegClientFactory', 'fromConfig']
        arguments:
            -   base_url: '%env(STEG_BASE_URL)%'
                model: '%env(STEG_MODEL)%'
                api_key: '%env(default:EMPTY:STEG_API_KEY)%'
                timeout: 120
```

## Dual-Provider Pattern

Steg works well as a stable fallback behind a feature flag or ENV switch:

```php
use Steg\Client\InferenceClientInterface;

$provider = $_ENV['LLM_GATEWAY_PROVIDER'] ?? 'symfony-ai';

$gateway = match ($provider) {
    'steg'  => new StegGateway($steg),
    default => new SymfonyAiGateway($platform),
};
```

```env
# Switch to Steg at runtime
LLM_GATEWAY_PROVIDER=steg
```

This pattern is useful when `symfony/ai-platform` is the primary provider but Steg
serves as a reliable fallback with a BC-stable interface.
