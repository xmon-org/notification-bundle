<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Channel;

use Xmon\NotificationBundle\Notification\NotificationInterface;
use Xmon\NotificationBundle\Recipient\RecipientInterface;
use Xmon\NotificationBundle\Result\NotificationResult;

interface ChannelInterface
{
    /**
     * Send a notification through this channel.
     */
    public function send(NotificationInterface $notification, RecipientInterface $recipient): NotificationResult;

    /**
     * Check if this channel supports the given channel name.
     */
    public function supports(string $channel): bool;

    /**
     * Check if this channel is properly configured and ready to send.
     */
    public function isConfigured(): bool;

    /**
     * Get the channel name identifier.
     */
    public function getName(): string;

    /**
     * Get retry priority for this channel (higher = more important).
     */
    public function getRetryPriority(): int;
}
