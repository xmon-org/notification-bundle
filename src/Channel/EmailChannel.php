<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Channel;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Xmon\NotificationBundle\Notification\NotificationInterface;
use Xmon\NotificationBundle\Recipient\RecipientInterface;
use Xmon\NotificationBundle\Result\NotificationResult;
use Xmon\NotificationBundle\Result\ResultStatus;
use Xmon\NotificationBundle\Service\TemplateRenderer;

/**
 * Email notification channel using Symfony Mailer.
 */
final class EmailChannel extends AbstractChannel
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TemplateRenderer $templateRenderer,
        private readonly string $from,
        private readonly string $fromName = '',
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
    }

    protected function doSend(NotificationInterface $notification, RecipientInterface $recipient): NotificationResult
    {
        $recipientEmail = $recipient->getEmail();

        if ($recipientEmail === null) {
            return new NotificationResult(
                channel: $this->getName(),
                status: ResultStatus::Failed,
                message: 'Recipient has no email address',
            );
        }

        try {
            // Render email body
            $htmlBody = $this->templateRenderer->isAvailable()
                ? $this->templateRenderer->render($notification, 'html')
                : $notification->getContent();

            // Create email
            $email = (new Email())
                ->from($this->fromName !== '' ? \sprintf('%s <%s>', $this->fromName, $this->from) : $this->from)
                ->to($recipientEmail)
                ->subject($notification->getTitle())
                ->html($htmlBody);

            // Send email
            $this->mailer->send($email);

            return new NotificationResult(
                channel: $this->getName(),
                status: ResultStatus::Success,
                message: \sprintf('Email sent to %s', $recipientEmail),
            );
        } catch (TransportExceptionInterface $e) {
            return new NotificationResult(
                channel: $this->getName(),
                status: ResultStatus::Failed,
                message: \sprintf('Failed to send email: %s', $e->getMessage()),
                metadata: ['exception' => $e::class],
            );
        }
    }

    public function isConfigured(): bool
    {
        // Check if Symfony Mailer is available
        return interface_exists(MailerInterface::class) && $this->from !== '';
    }

    public function getName(): string
    {
        return 'email';
    }

    public function getRetryPriority(): int
    {
        return 100; // High priority for email
    }
}
