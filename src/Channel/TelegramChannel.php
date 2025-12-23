<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Channel;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Xmon\NotificationBundle\Notification\NotificationInterface;
use Xmon\NotificationBundle\Recipient\RecipientInterface;
use Xmon\NotificationBundle\Result\NotificationResult;
use Xmon\NotificationBundle\Result\ResultStatus;

/**
 * Telegram channel - sends notifications via Telegram Bot API.
 */
final class TelegramChannel extends AbstractChannel
{
    private const API_URL = 'https://api.telegram.org/bot%s/sendMessage';

    public function __construct(
        private readonly ?HttpClientInterface $httpClient,
        private readonly array $config,
        LoggerInterface $logger,
    ) {
        parent::__construct($logger);
    }

    public function getName(): string
    {
        return 'telegram';
    }

    public function supports(string $channel): bool
    {
        return $channel === 'telegram';
    }

    public function isConfigured(): bool
    {
        return $this->httpClient !== null
            && ($this->config['enabled'] ?? false)
            && !empty($this->config['bot_token'] ?? '');
    }

    public function getRetryPriority(): int
    {
        return 80; // Lower than email (100), but still high priority
    }

    protected function doSend(NotificationInterface $notification, RecipientInterface $recipient): NotificationResult
    {
        if (!$this->isConfigured()) {
            return new NotificationResult(
                channel: $this->getName(),
                status: ResultStatus::Failed,
                message: 'Telegram channel is not configured',
            );
        }

        $chatId = $recipient->getTelegramChatId() ?? ($this->config['default_chat_id'] ?? null);

        if (empty($chatId)) {
            return new NotificationResult(
                channel: $this->getName(),
                status: ResultStatus::Failed,
                message: 'No Telegram chat ID provided for recipient',
            );
        }

        try {
            $text = $this->formatMessage($notification);

            $response = $this->httpClient->request('POST', \sprintf(self::API_URL, $this->config['bot_token']), [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => $this->config['disable_preview'] ?? false,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode === 200 && ($content['ok'] ?? false)) {
                $messageId = (string) ($content['result']['message_id'] ?? '');
                $this->logger->info('Telegram notification sent', [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                ]);

                return new NotificationResult(
                    channel: $this->getName(),
                    status: ResultStatus::Success,
                    message: \sprintf('Telegram message sent (ID: %s)', $messageId),
                    metadata: ['message_id' => $messageId, 'chat_id' => $chatId],
                );
            }

            $errorMsg = $content['description'] ?? 'Unknown Telegram API error';
            $this->logger->error('Telegram API error', [
                'status_code' => $statusCode,
                'error' => $errorMsg,
            ]);

            return new NotificationResult(
                channel: $this->getName(),
                status: ResultStatus::Failed,
                message: $errorMsg,
            );
        } catch (\Throwable $e) {
            $this->logger->error('Telegram notification failed', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);

            return new NotificationResult(
                channel: $this->getName(),
                status: ResultStatus::Failed,
                message: $e->getMessage(),
            );
        }
    }

    private function formatMessage(NotificationInterface $notification): string
    {
        $title = $notification->getTitle();
        $content = $notification->getContent();
        $priority = $notification->getPriority();

        // Add priority emoji
        $emoji = match ($priority->value) {
            'urgent' => '🚨',
            'high' => '⚠️',
            'normal' => 'ℹ️',
            'low' => '📝',
            default => '',
        };

        $message = '';

        if ($emoji) {
            $message .= $emoji.' ';
        }

        $message .= "*{$title}*\n\n";
        $message .= $content;

        // Add metadata if present
        $metadata = $notification->getMetadata();
        if (!empty($metadata['url'])) {
            $message .= "\n\n🔗 [Ver más]({$metadata['url']})";
        }

        return $message;
    }
}
