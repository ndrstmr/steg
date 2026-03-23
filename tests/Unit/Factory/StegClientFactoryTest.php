<?php

declare(strict_types=1);

namespace Steg\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Steg\Client\MockClient;
use Steg\Factory\StegClientFactory;
use Steg\StegClient;

final class StegClientFactoryTest extends TestCase
{
    public function testFromMockDsn(): void
    {
        $client = StegClientFactory::fromDsn('mock://default');

        self::assertInstanceOf(StegClient::class, $client);
        self::assertInstanceOf(MockClient::class, $client->getClient());
    }

    public function testFromMockDsnWithCustomResponse(): void
    {
        $client = StegClientFactory::fromDsn('mock://default?response=Hello+World&model=test-model');

        self::assertInstanceOf(MockClient::class, $client->getClient());
        self::assertSame('Hello World', $client->ask('anything'));
    }

    public function testFromConfigWithExplicitHttpClientBuildsClient(): void
    {
        $httpClient = $this->createMock(\Symfony\Contracts\HttpClient\HttpClientInterface::class);

        $client = StegClientFactory::fromConfig([
            'base_url' => 'http://localhost:8000/v1',
            'model' => 'test-model',
        ], $httpClient);

        self::assertInstanceOf(StegClient::class, $client);
    }

    public function testFromDsnMissingModelThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/missing required "model"/');

        StegClientFactory::fromDsn('vllm://localhost:8000/v1');
    }

    public function testFromConfigMissingBaseUrlThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"base_url"/');

        StegClientFactory::fromConfig(['base_url' => '', 'model' => 'test']);
    }

    public function testFromConfigMissingModelThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"model"/');

        StegClientFactory::fromConfig(['base_url' => 'http://localhost/v1', 'model' => '']);
    }

    public function testInvalidDsnThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        StegClientFactory::fromDsn('not-a-dsn');
    }
}
