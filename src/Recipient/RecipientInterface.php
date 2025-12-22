<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Recipient;

interface RecipientInterface
{
    /**
     * Get recipient's email address.
     */
    public function getEmail(): ?string;

    /**
     * Get recipient's Telegram chat ID.
     */
    public function getTelegramChatId(): ?string;

    /**
     * Get recipient's Discord webhook URL.
     */
    public function getDiscordWebhook(): ?string;

    /**
     * Get recipient's Slack webhook URL.
     */
    public function getSlackWebhook(): ?string;

    /**
     * Get recipient's user ID (for in-app notifications).
     */
    public function getUserId(): ?int;

    /**
     * Get recipient's locale for localized notifications.
     */
    public function getLocale(): string;

    /**
     * Get channel-specific identifier.
     */
    public function getChannelIdentifier(string $channelName): ?string;
}
