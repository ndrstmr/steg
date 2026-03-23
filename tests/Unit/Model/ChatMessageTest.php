<?php

declare(strict_types=1);

namespace Steg\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Steg\Model\ChatMessage;

final class ChatMessageTest extends TestCase
{
    public function testUserFactory(): void
    {
        $msg = ChatMessage::user('Hello');

        self::assertSame('user', $msg->role);
        self::assertSame('Hello', $msg->content);
    }

    public function testSystemFactory(): void
    {
        $msg = ChatMessage::system('You are helpful.');

        self::assertSame('system', $msg->role);
        self::assertSame('You are helpful.', $msg->content);
    }

    public function testAssistantFactory(): void
    {
        $msg = ChatMessage::assistant('I can help you.');

        self::assertSame('assistant', $msg->role);
    }

    public function testToArray(): void
    {
        $msg = ChatMessage::user('Test');

        self::assertSame(['role' => 'user', 'content' => 'Test'], $msg->toArray());
    }

    public function testInvalidRoleThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid role "admin"/');

        new ChatMessage('admin', 'content');
    }
}
