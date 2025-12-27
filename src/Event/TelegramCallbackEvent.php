<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched when a Telegram callback query (button click) is received.
 *
 * Listen to this event to handle inline keyboard button clicks.
 * Call setHandled(true) and optionally setResponseText() to provide feedback.
 */
final class TelegramCallbackEvent extends Event
{
    private bool $handled = false;
    private string $responseText = '';
    private bool $showAlert = false;

    public function __construct(
        private readonly string $callbackQueryId,
        private readonly string $callbackData,
        private readonly string $chatId,
        private readonly int $messageId,
        private readonly array $from,
        private readonly array $rawUpdate,
    ) {
    }

    /**
     * The callback query ID (required for answerCallbackQuery).
     */
    public function getCallbackQueryId(): string
    {
        return $this->callbackQueryId;
    }

    /**
     * The callback data from the button (e.g., "publish:123").
     */
    public function getCallbackData(): string
    {
        return $this->callbackData;
    }

    /**
     * Parse callback data in "action:id" or "action:id:extra" format.
     *
     * @return array{action: string, id: int|null, extra: string|null}
     */
    public function parseCallbackData(): array
    {
        $parts = explode(':', $this->callbackData, 3);

        return [
            'action' => $parts[0],
            'id' => isset($parts[1]) ? (int) $parts[1] : null,
            'extra' => $parts[2] ?? null,
        ];
    }

    /**
     * The chat ID where the message was sent.
     */
    public function getChatId(): string
    {
        return $this->chatId;
    }

    /**
     * The message ID that contains the inline keyboard.
     */
    public function getMessageId(): int
    {
        return $this->messageId;
    }

    /**
     * Information about the user who clicked the button.
     *
     * @return array{id: int, first_name: string, last_name?: string, username?: string}
     */
    public function getFrom(): array
    {
        return $this->from;
    }

    /**
     * The raw update payload from Telegram.
     */
    public function getRawUpdate(): array
    {
        return $this->rawUpdate;
    }

    /**
     * Mark this callback as handled.
     * If no listener handles it, a generic "Not implemented" response will be sent.
     */
    public function setHandled(bool $handled): void
    {
        $this->handled = $handled;
    }

    public function isHandled(): bool
    {
        return $this->handled;
    }

    /**
     * Set the response text to show to the user (max 200 chars).
     */
    public function setResponseText(string $text): void
    {
        $this->responseText = $text;
    }

    public function getResponseText(): string
    {
        return $this->responseText;
    }

    /**
     * Show response as an alert popup instead of a toast notification.
     */
    public function setShowAlert(bool $showAlert): void
    {
        $this->showAlert = $showAlert;
    }

    public function shouldShowAlert(): bool
    {
        return $this->showAlert;
    }
}
