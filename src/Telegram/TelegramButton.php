<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Telegram;

/**
 * DTO for Telegram inline keyboard buttons.
 *
 * Supports two types:
 * - Callback: triggers a callback_query with custom data
 * - URL: opens a URL in the user's browser
 */
final class TelegramButton
{
    private function __construct(
        public readonly string $text,
        public readonly ?string $callbackData = null,
        public readonly ?string $url = null,
    ) {
    }

    /**
     * Create a callback button that triggers a callback_query.
     *
     * @param string $text Button label
     * @param string $data Callback data (max 64 bytes)
     */
    public static function callback(string $text, string $data): self
    {
        return new self(text: $text, callbackData: $data);
    }

    /**
     * Create a URL button that opens a link.
     *
     * @param string $text Button label
     * @param string $url  URL to open
     */
    public static function url(string $text, string $url): self
    {
        return new self(text: $text, url: $url);
    }

    /**
     * Convert to Telegram API format.
     */
    public function toArray(): array
    {
        if ($this->callbackData !== null) {
            return [
                'text' => $this->text,
                'callback_data' => $this->callbackData,
            ];
        }

        return [
            'text' => $this->text,
            'url' => $this->url,
        ];
    }

    public function isCallback(): bool
    {
        return $this->callbackData !== null;
    }

    public function isUrl(): bool
    {
        return $this->url !== null;
    }
}
