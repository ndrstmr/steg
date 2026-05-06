<?php

declare(strict_types=1);

namespace Steg\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Steg\Client\InferenceClientInterface;
use Steg\Client\MockClient;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;
use Steg\StegClient;

final class StegClientTest extends TestCase
{
    public function testImplementsInferenceClientInterface(): void
    {
        $client = new StegClient(new MockClient());

        self::assertInstanceOf(InferenceClientInterface::class, $client);
    }

    public function testCompleteDelegatesToUnderlyingClient(): void
    {
        $client = new StegClient(new MockClient(response: 'pong', model: 'mock'));

        $response = $client->complete(
            [ChatMessage::user('ping')],
            CompletionOptions::default(),
        );

        self::assertSame('pong', $response->content);
        self::assertSame('mock', $response->model);
    }

    public function testIsHealthyDelegates(): void
    {
        $healthy = new StegClient(new MockClient());
        $unhealthy = new StegClient(MockClient::unhealthy());

        self::assertTrue($healthy->isHealthy());
        self::assertFalse($unhealthy->isHealthy());
    }
}
