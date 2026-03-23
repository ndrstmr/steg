# Symfony Integration

## Option A: ndrstmr/steg-bundle (recommended)

The optional bundle provides automatic DI configuration, parameter binding, and a Symfony Profiler panel.

```bash
composer require ndrstmr/steg-bundle
```

The bundle auto-registers `StegClient` as a service when `STEG_DSN` is set:

```env
# .env
STEG_DSN=vllm://localhost:8000/v1?model=llama-3.3-70b-awq
```

```php
// Inject StegClient directly
class TranslationController
{
    public function __construct(private readonly StegClient $steg) {}
}
```

## Option B: Manual Symfony service wiring

Without the bundle, register the client manually in `config/services.yaml`:

```yaml
# config/services.yaml
services:
    Steg\StegClient:
        factory: ['Steg\Factory\StegClientFactory', 'fromDsn']
        arguments:
            - '%env(STEG_DSN)%'

    # Or with explicit config
    Steg\StegClient:
        factory: ['Steg\Factory\StegClientFactory', 'fromConfig']
        arguments:
            -   base_url: '%env(STEG_BASE_URL)%'
                model: '%env(STEG_MODEL)%'
                api_key: '%env(default:EMPTY:STEG_API_KEY)%'
                timeout: 120
```

## Using Steg as LLM_GATEWAY_PROVIDER fallback

In the LS-KI Portal-KI-Plattform, Steg serves as the fallback gateway:

```php
// LlmGatewayInterface implementation selector
$provider = $_ENV['LLM_GATEWAY_PROVIDER'] ?? 'symfony-ai';

$gateway = match ($provider) {
    'steg'  => new StegGateway($steg),
    default => new SymfonyAiGateway($platform),
};
```

```env
# Switch to Steg fallback
LLM_GATEWAY_PROVIDER=steg
```
