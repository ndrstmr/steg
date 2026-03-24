<?php

declare(strict_types=1);

namespace Steg\Tests\Unit\Client;

use PHPUnit\Framework\TestCase;
use Steg\Client\MockClient;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;

final class MockClientTest extends TestCase
{
    public function testCompleteReturnsConfiguredResponse(): void
    {
        $client = new MockClient(response: 'Fixed answer.');
        $response = $client->complete([ChatMessage::user('Hello')]);

        self::assertSame('Fixed answer.', $response->content);
        self::assertSame('mock-model', $response->model);
    }

    public function testWithResponsesCyclesThroughThem(): void
    {
        $client = MockClient::withResponses(['First', 'Second', 'Third']);

        self::assertSame('First', $client->complete([ChatMessage::user('q')])->content);
        self::assertSame('Second', $client->complete([ChatMessage::user('q')])->content);
        self::assertSame('Third', $client->complete([ChatMessage::user('q')])->content);
        // Loops back
        self::assertSame('First', $client->complete([ChatMessage::user('q')])->content);
    }

    public function testCallCountIncrements(): void
    {
        $client = new MockClient();
        $client->complete([ChatMessage::user('a')]);
        $client->complete([ChatMessage::user('b')]);

        self::assertSame(2, $client->getCallCount());
    }

    public function testResetClearsCallCount(): void
    {
        $client = new MockClient();
        $client->complete([ChatMessage::user('a')]);
        $client->reset();

        self::assertSame(0, $client->getCallCount());
    }

    public function testIsHealthyDefault(): void
    {
        self::assertTrue((new MockClient())->isHealthy());
    }

    public function testUnhealthyClient(): void
    {
        self::assertFalse(MockClient::unhealthy()->isHealthy());
    }

    public function testListModels(): void
    {
        $models = (new MockClient())->listModels();

        self::assertCount(1, $models);
        self::assertSame('mock-model', $models[0]->id);
    }

    public function testStreamYieldsWords(): void
    {
        $client = new MockClient(response: 'Hello World Test');
        $chunks = iterator_to_array($client->stream([ChatMessage::user('hi')]));

        $collected = implode('', array_map(static fn ($c) => $c->delta, $chunks));

        self::assertSame('Hello World Test', $collected);
        self::assertTrue($chunks[\count($chunks) - 1]->isLast);
    }

    public function testCompleteAcceptsOptions(): void
    {
        $client = new MockClient(response: 'ok');
        $response = $client->complete(
            [ChatMessage::user('test')],
            CompletionOptions::precise(),
        );

        self::assertSame('ok', $response->content);
    }

    public function testWithCallbackUsesCallbackForComplete(): void
    {
        $client = (new MockClient())->withCallback(
            static fn (array $messages, ?CompletionOptions $opts): string => 'Dynamic: '.$messages[0]->content,
        );

        $response = $client->complete([ChatMessage::user('hello')]);

        self::assertSame('Dynamic: hello', $response->content);
    }

    public function testWithCallbackUsesCallbackForStream(): void
    {
        $client = (new MockClient())->withCallback(
            static fn (array $messages, ?CompletionOptions $opts): string => 'CB response',
        );

        $chunks = iterator_to_array($client->stream([ChatMessage::user('hi')]));
        $collected = implode('', array_map(static fn ($c) => $c->delta, $chunks));

        self::assertSame('CB response', $collected);
    }

    public function testWithCallbackDoesNotMutateOriginal(): void
    {
        $original = new MockClient(response: 'original');
        $withCb = $original->withCallback(static fn () => 'from callback');

        self::assertSame('original', $original->complete([ChatMessage::user('x')])->content);
        self::assertSame('from callback', $withCb->complete([ChatMessage::user('x')])->content);
    }
}
