# Getting Started with Steg

## Installation

```bash
composer require ndrstmr/steg symfony/http-client
```

## Create your first client

### From DSN (recommended)

```php
use Steg\Factory\StegClientFactory;

$steg = StegClientFactory::fromDsn('vllm://localhost:8000/v1?model=llama-3.3-70b-awq');
```

### From config array

```php
$steg = StegClientFactory::fromConfig([
    'base_url' => 'http://localhost:8000/v1',
    'model'    => 'llama-3.3-70b-awq',
]);
```

## Send your first request

```php
echo $steg->ask('What is 2 + 2?');
// → "2 + 2 equals 4."
```

## Check server health

```php
if (! $steg->isHealthy()) {
    throw new \RuntimeException('Inference server is not reachable!');
}
```

## Next steps

- [Configuration reference](configuration.md)
- [Supported backends](supported-backends.md)
- [Symfony integration](symfony-integration.md)
