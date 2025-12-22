<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Channel;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Xmon\NotificationBundle\Notification\NotificationInterface;
use Xmon\NotificationBundle\Recipient\RecipientInterface;
use Xmon\NotificationBundle\Result\NotificationResult;
use Xmon\NotificationBundle\Result\ResultStatus;

abstract class AbstractChannel implements ChannelInterface
{
    protected LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function send(NotificationInterface $notification, RecipientInterface $recipient): NotificationResult
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Channel {channel} is not configured, skipping', [
                'channel' => $this->getName(),
            ]);

            return new NotificationResult(
                channel: $this->getName(),
                status: ResultStatus::Failed,
                message: sprintf('Channel %s is not configured', $this->getName()),
            );
        }

        try {
            $this->logger->info('Sending notification via {channel}', [
                'channel' => $this->getName(),
                'title' => $notification->getTitle(),
            ]);

            return $this->doSend($notification, $recipient);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send notification via {channel}: {error}', [
                'channel' => $this->getName(),
                'error' => $e->getMessage(),
                'exception' => $e,
            ]);

            return new NotificationResult(
                channel: $this->getName(),
                status: ResultStatus::Failed,
                message: $e->getMessage(),
                metadata: ['exception' => $e::class],
            );
        }
    }

    /**
     * Actual sending logic implemented by concrete channels.
     */
    abstract protected function doSend(NotificationInterface $notification, RecipientInterface $recipient): NotificationResult;

    public function supports(string $channel): bool
    {
        return $channel === $this->getName();
    }

    public function getRetryPriority(): int
    {
        return 50; // Default priority
    }
}
