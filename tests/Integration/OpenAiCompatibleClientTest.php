<?php

declare(strict_types=1);

namespace Steg\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Steg\Client\OpenAiCompatibleClient;
use Steg\Exception\InferenceException;
use Steg\Model\ChatMessage;
use Steg\Model\CompletionOptions;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenAiCompatibleClientTest extends TestCase
{
    private function makeSuccessResponse(string $content = 'Test response', string $model = 'llama-3.3-70b'): string
    {
        $encoded = json_encode([
            'id' => 'chatcmpl-test',
            'object' => 'chat.completion',
            'model' => $model,
            'choices' => [
                [
                    'index' => 0,
                    'message' => ['role' => 'assistant', 'content' => $content],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 20,
                'completion_tokens' => 10,
                'total_tokens' => 30,
            ],
        ], \JSON_THROW_ON_ERROR);

        return false !== $encoded ? $encoded : '';
    }

    private function makeModelsResponse(): string
    {
        $encoded = json_encode([
            'object' => 'list',
            'data' => [
                ['id' => 'llama-3.3-70b', 'object' => 'model', 'owned_by' => 'vllm'],
                ['id' => 'mistral-small', 'object' => 'model', 'owned_by' => 'vllm'],
            ],
        ], \JSON_THROW_ON_ERROR);

        return false !== $encoded ? $encoded : '';
    }

    public function testCompleteWithMockHttpClient(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse($this->makeSuccessResponse('Hello from vLLM!')),
        );

        $client = new OpenAiCompatibleClient(
            httpClient: $httpClient,
            baseUrl: 'http://localhost:8000/v1',
            model: 'llama-3.3-70b',
        );

        $response = $client->complete([ChatMessage::user('Hi')]);

        self::assertSame('Hello from vLLM!', $response->content);
        self::assertSame('llama-3.3-70b', $response->model);
        self::assertSame('stop', $response->finishReason);
    }

    public function testCompleteWithOptions(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse($this->makeSuccessResponse()),
        );

        $client = new OpenAiCompatibleClient(
            httpClient: $httpClient,
            baseUrl: 'http://localhost:8000/v1',
            model: 'llama-3.3-70b',
        );

        $response = $client->complete(
            [ChatMessage::user('Translate this.')],
            CompletionOptions::leichteSprache(),
        );

        self::assertSame('Test response', $response->content);
    }

    public function testListModels(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse($this->makeModelsResponse()),
        );

        $client = new OpenAiCompatibleClient(
            httpClient: $httpClient,
            baseUrl: 'http://localhost:8000/v1',
            model: 'llama-3.3-70b',
        );

        $models = $client->listModels();

        self::assertCount(2, $models);
        self::assertSame('llama-3.3-70b', $models[0]->id);
        self::assertSame('mistral-small', $models[1]->id);
    }

    public function testIsHealthyReturnsTrueOn200(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse($this->makeModelsResponse()),
        );

        $client = new OpenAiCompatibleClient(
            httpClient: $httpClient,
            baseUrl: 'http://localhost:8000/v1',
            model: 'llama-3.3-70b',
        );

        self::assertTrue($client->isHealthy());
    }

    public function testIsHealthyReturnsFalseOn500(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('Internal Server Error', ['http_code' => 500]),
        );

        $client = new OpenAiCompatibleClient(
            httpClient: $httpClient,
            baseUrl: 'http://localhost:8000/v1',
            model: 'llama-3.3-70b',
        );

        self::assertFalse($client->isHealthy());
    }

    public function testCompleteThrowsInferenceExceptionOn400(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse('{"error": "Bad request"}', ['http_code' => 400]),
        );

        $client = new OpenAiCompatibleClient(
            httpClient: $httpClient,
            baseUrl: 'http://localhost:8000/v1',
            model: 'llama-3.3-70b',
        );

        $this->expectException(InferenceException::class);

        $client->complete([ChatMessage::user('test')]);
    }

    public function testCompleteWithSystemMessage(): void
    {
        $httpClient = new MockHttpClient(
            new MockResponse($this->makeSuccessResponse('Translated text')),
        );

        $client = new OpenAiCompatibleClient(
            httpClient: $httpClient,
            baseUrl: 'http://localhost:8000/v1',
            model: 'llama-3.3-70b',
        );

        $response = $client->complete([
            ChatMessage::system('You translate to Leichte Sprache.'),
            ChatMessage::user('Translate: The government passed new legislation.'),
        ]);

        self::assertSame('Translated text', $response->content);
    }
}
