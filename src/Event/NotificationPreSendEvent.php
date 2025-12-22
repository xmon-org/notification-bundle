<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Xmon\NotificationBundle\Notification\NotificationInterface;
use Xmon\NotificationBundle\Recipient\RecipientInterface;

/**
 * Event dispatched before sending a notification.
 * Allows modification or cancellation.
 */
final class NotificationPreSendEvent extends Event
{
    private bool $cancelled = false;

    public function __construct(
        public readonly NotificationInterface $notification,
        public readonly RecipientInterface $recipient,
        public readonly string $channel,
    ) {
    }

    public function cancel(): void
    {
        $this->cancelled = true;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }
}
