# Symfony 7.4 Patterns — Steg Context

## HttpClient with Contracts (Core Pattern)

Steg core only depends on `symfony/http-client-contracts` — the concrete
`symfony/http-client` is a dev/runtime suggestion.

```php
<?php

declare(strict_types=1);

namespace Steg\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class OpenAiCompatibleClient implements InferenceClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly string $apiKey = 'EMPTY',
        private readonly int $timeout = 120,
    ) {
    }

    public function complete(array $messages, ?CompletionOptions $options = null): CompletionResponse
    {
        try {
            $response = $this->httpClient->request('POST', $this->baseUrl.'/chat/completions', [
                'headers' => ['Authorization' => 'Bearer '.$this->apiKey],
                'json'    => $this->buildPayload($messages, $options),
                'timeout' => $this->timeout,
            ]);

            $statusCode = $response->getStatusCode();
            $body       = $response->getContent(false);
        } catch (TransportExceptionInterface $e) {
            throw ConnectionException::unreachable($this->baseUrl, $e);
        }

        // ...
    }
}
```

## Streaming with HttpClient

```php
<?php

declare(strict_types=1);

public function stream(array $messages, ?CompletionOptions $options = null): \Generator
{
    $response = $this->httpClient->request('POST', $this->baseUrl.'/chat/completions', [
        'json'    => array_merge($this->buildPayload($messages, $options), ['stream' => true]),
        'buffer'  => false,
        'timeout' => $this->timeout,
    ]);

    foreach ($this->httpClient->stream($response) as $chunk) {
        if ($chunk->isLast()) {
            break;
        }

        foreach (explode("\n", trim($chunk->getContent())) as $line) {
            if (!str_starts_with($line, 'data: ')) {
                continue;
            }

            $json = substr($line, 6);
            if ('[DONE]' === $json) {
                return;
            }

            yield StreamChunk::fromSseData(
                json_decode($json, true, 512, \JSON_THROW_ON_ERROR),
            );
        }
    }
}
```

## Dependency Injection (for steg-bundle)

```yaml
# config/services.yaml (in ndrstmr/steg-bundle)
services:
    Steg\StegClient:
        factory: ['Steg\Factory\StegClientFactory', 'fromDsn']
        arguments:
            - '%env(STEG_DSN)%'

    Steg\Client\InferenceClientInterface:
        alias: Steg\StegClient
```

## Bundle Structure (ndrstmr/steg-bundle)

```
src/
├── StegBundle.php
├── DependencyInjection/
│   ├── StegExtension.php
│   └── Configuration.php
├── DataCollector/
│   └── StegDataCollector.php   # Profiler panel
└── Resources/
    └── config/
        └── services.xml
```

## Bundle Extension Pattern

```php
<?php

declare(strict_types=1);

namespace Steg\Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class StegExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $container->setParameter('steg.dsn', $config['dsn']);
        $container->setParameter('steg.model', $config['model']);
    }
}
```

## Configuration Tree (Bundle)

```php
<?php

declare(strict_types=1);

namespace Steg\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('steg');
        $rootNode    = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('dsn')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('model')->isRequired()->cannotBeEmpty()->end()
                ->integerNode('timeout')->defaultValue(120)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
```

## Contracts — The Right Abstraction Level

| Use | Don't use |
|-----|-----------|
| `symfony/http-client-contracts` in core | `symfony/http-client` in core |
| `psr/log` for logging | `monolog/monolog` directly |
| `Symfony\Contracts\HttpClient\HttpClientInterface` | `Symfony\Component\HttpClient\HttpClient` |

## ENV Variable Pattern (LS-KI)

```env
# Switch between providers
LLM_GATEWAY_PROVIDER=steg        # use StegGateway
LLM_GATEWAY_PROVIDER=symfony-ai  # use SymfonyAiGateway

STEG_DSN=vllm://localhost:8000/v1?model=llama-3.3-70b-awq
```

```php
<?php

declare(strict_types=1);

$gateway = match ($_ENV['LLM_GATEWAY_PROVIDER'] ?? 'symfony-ai') {
    'steg'  => new StegGateway($stegClient),
    default => new SymfonyAiGateway($platform),
};
```
