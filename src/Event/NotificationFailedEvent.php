<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Xmon\NotificationBundle\Notification\NotificationInterface;
use Xmon\NotificationBundle\Recipient\RecipientInterface;
use Xmon\NotificationBundle\Result\NotificationResult;

/**
 * Event dispatched when a notification fails to send.
 */
final class NotificationFailedEvent extends Event
{
    public function __construct(
        public readonly NotificationInterface $notification,
        public readonly RecipientInterface $recipient,
        public readonly NotificationResult $result,
    ) {
    }
}
