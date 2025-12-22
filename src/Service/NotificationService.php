<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Service;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Xmon\NotificationBundle\Channel\ChannelRegistry;
use Xmon\NotificationBundle\Event\NotificationFailedEvent;
use Xmon\NotificationBundle\Event\NotificationPreSendEvent;
use Xmon\NotificationBundle\Event\NotificationSentEvent;
use Xmon\NotificationBundle\Notification\NotificationInterface;
use Xmon\NotificationBundle\Recipient\RecipientInterface;
use Xmon\NotificationBundle\Result\NotificationResult;

/**
 * Main notification service - orchestrates sending notifications through channels.
 */
final readonly class NotificationService
{
    public function __construct(
        private ChannelRegistry $channelRegistry,
        private EventDispatcherInterface $eventDispatcher,
        private array $defaultChannels = ['email'],
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Send a notification to a recipient through configured channels.
     *
     * @return array<NotificationResult>
     */
    public function send(NotificationInterface $notification, RecipientInterface $recipient): array
    {
        $logger = $this->logger ?? new NullLogger();
        $channels = $notification->getChannels() ?: $this->defaultChannels;
        $results = [];

        foreach ($channels as $channelName) {
            // Pre-send event (allows modification or cancellation)
            $preSendEvent = new NotificationPreSendEvent($notification, $recipient, $channelName);
            $this->eventDispatcher->dispatch($preSendEvent);

            if ($preSendEvent->isCancelled()) {
                $logger->info('Notification cancelled by event listener', [
                    'channel' => $channelName,
                    'title' => $notification->getTitle(),
                ]);
                continue;
            }

            // Get channel
            if (!$this->channelRegistry->hasChannel($channelName)) {
                $logger->warning('Channel not available: {channel}', ['channel' => $channelName]);
                continue;
            }

            $channel = $this->channelRegistry->getChannel($channelName);

            // Send notification
            $result = $channel->send($notification, $recipient);
            $results[] = $result;

            // Dispatch result events
            if ($result->isSuccess()) {
                $this->eventDispatcher->dispatch(
                    new NotificationSentEvent($notification, $recipient, $result)
                );

                $logger->info('Notification sent successfully via {channel}', [
                    'channel' => $channelName,
                    'title' => $notification->getTitle(),
                ]);
            } else {
                $this->eventDispatcher->dispatch(
                    new NotificationFailedEvent($notification, $recipient, $result)
                );

                $logger->error('Notification failed via {channel}: {message}', [
                    'channel' => $channelName,
                    'message' => $result->message,
                    'title' => $notification->getTitle(),
                ]);
            }
        }

        return $results;
    }
}
