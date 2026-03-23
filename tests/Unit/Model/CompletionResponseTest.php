<?php

declare(strict_types=1);

namespace Steg\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Steg\Exception\InvalidResponseException;
use Steg\Model\CompletionResponse;

final class CompletionResponseTest extends TestCase
{
    public function testFromApiResponse(): void
    {
        $data = [
            'id' => 'chatcmpl-abc123',
            'model' => 'llama-3.3-70b',
            'choices' => [
                [
                    'message' => ['role' => 'assistant', 'content' => 'Hello!'],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 5,
            ],
        ];

        $response = CompletionResponse::fromApiResponse($data, 123.4);

        self::assertSame('Hello!', $response->content);
        self::assertSame('llama-3.3-70b', $response->model);
        self::assertSame(10, $response->promptTokens);
        self::assertSame(5, $response->completionTokens);
        self::assertSame(15, $response->totalTokens());
        self::assertSame('stop', $response->finishReason);
        self::assertSame(123.4, $response->durationMs);
        self::assertSame('chatcmpl-abc123', $response->id);
    }

    public function testFromApiResponseMissingChoicesThrows(): void
    {
        $this->expectException(InvalidResponseException::class);

        CompletionResponse::fromApiResponse([], 0.0);
    }

    public function testFromApiResponseMissingContentThrows(): void
    {
        $this->expectException(InvalidResponseException::class);

        CompletionResponse::fromApiResponse([
            'choices' => [['message' => ['role' => 'assistant']]],
        ], 0.0);
    }

    public function testTotalTokens(): void
    {
        $response = new CompletionResponse(
            content: 'test',
            model: 'test-model',
            promptTokens: 42,
            completionTokens: 8,
            finishReason: 'stop',
            durationMs: 0.0,
        );

        self::assertSame(50, $response->totalTokens());
    }
}
