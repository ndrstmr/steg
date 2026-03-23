<?php

declare(strict_types=1);

namespace Steg\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Steg\Model\CompletionOptions;

final class CompletionOptionsTest extends TestCase
{
    public function testDefaultPreset(): void
    {
        $opts = CompletionOptions::default();

        self::assertSame(0.7, $opts->temperature);
        self::assertSame(4096, $opts->maxTokens);
    }

    public function testPrecisePreset(): void
    {
        $opts = CompletionOptions::precise();

        self::assertSame(0.1, $opts->temperature);
    }

    public function testCreativePreset(): void
    {
        $opts = CompletionOptions::creative();

        self::assertSame(0.9, $opts->temperature);
    }

    public function testLeichteSprachePreset(): void
    {
        $opts = CompletionOptions::leichteSprache();

        self::assertSame(0.3, $opts->temperature);
        self::assertSame(4096, $opts->maxTokens);
    }

    public function testWithTemperatureReturnsNewInstance(): void
    {
        $original = CompletionOptions::default();
        $modified = $original->withTemperature(0.5);

        self::assertSame(0.7, $original->temperature);
        self::assertSame(0.5, $modified->temperature);
        self::assertSame($original->maxTokens, $modified->maxTokens);
    }

    public function testWithMaxTokensReturnsNewInstance(): void
    {
        $original = CompletionOptions::default();
        $modified = $original->withMaxTokens(1024);

        self::assertSame(4096, $original->maxTokens);
        self::assertSame(1024, $modified->maxTokens);
    }

    public function testToArray(): void
    {
        $opts = CompletionOptions::precise();
        $array = $opts->toArray();

        self::assertArrayHasKey('temperature', $array);
        self::assertArrayHasKey('max_tokens', $array);
        self::assertSame(0.1, $array['temperature']);
        self::assertArrayNotHasKey('stop', $array);
    }

    public function testToArrayIncludesStopWhenSet(): void
    {
        $opts = new CompletionOptions(stop: ['</s>', '[STOP]']);
        $array = $opts->toArray();

        self::assertArrayHasKey('stop', $array);
        self::assertSame(['</s>', '[STOP]'], $array['stop']);
    }
}
