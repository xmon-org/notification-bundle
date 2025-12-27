<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a text message is received via Telegram webhook.
 *
 * Allows application to handle user text responses in guided conversations.
 */
final class TelegramMessageEvent extends Event
{
    private bool $handled = false;
    private ?string $responseMessage = null;

    public function __construct(
        private readonly string $chatId,
        private readonly string $userId,
        private readonly string $text,
        private readonly int $messageId,
        private readonly array $from,
        private readonly ?int $replyToMessageId,
        private readonly array $rawUpdate,
    ) {
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }

    public function getFrom(): array
    {
        return $this->from;
    }

    /**
     * Get the message ID this message is replying to (if any).
     */
    public function getReplyToMessageId(): ?int
    {
        return $this->replyToMessageId;
    }

    public function getRawUpdate(): array
    {
        return $this->rawUpdate;
    }

    public function isHandled(): bool
    {
        return $this->handled;
    }

    public function setHandled(bool $handled): void
    {
        $this->handled = $handled;
    }

    public function getResponseMessage(): ?string
    {
        return $this->responseMessage;
    }

    public function setResponseMessage(?string $message): void
    {
        $this->responseMessage = $message;
    }

    /**
     * Get the username or first name of the sender.
     */
    public function getSenderName(): string
    {
        return $this->from['username'] ?? $this->from['first_name'] ?? 'Usuario';
    }
}
