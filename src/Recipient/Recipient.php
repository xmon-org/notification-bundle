<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Recipient;

/**
 * Simple recipient implementation.
 */
final readonly class Recipient implements RecipientInterface
{
    public function __construct(
        private ?string $email = null,
        private ?string $telegramChatId = null,
        private ?string $discordWebhook = null,
        private ?string $slackWebhook = null,
        private ?int $userId = null,
        private string $locale = 'en',
    ) {
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getTelegramChatId(): ?string
    {
        return $this->telegramChatId;
    }

    public function getDiscordWebhook(): ?string
    {
        return $this->discordWebhook;
    }

    public function getSlackWebhook(): ?string
    {
        return $this->slackWebhook;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getChannelIdentifier(string $channelName): ?string
    {
        return match ($channelName) {
            'email' => $this->email,
            'telegram' => $this->telegramChatId,
            'discord' => $this->discordWebhook,
            'slack' => $this->slackWebhook,
            'in_app' => $this->userId !== null ? (string) $this->userId : null,
            default => null,
        };
    }
}
