<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Xmon\NotificationBundle\Event\TelegramCallbackEvent;
use Xmon\NotificationBundle\Telegram\TelegramService;

/**
 * Webhook controller for Telegram Bot API updates.
 *
 * Handles:
 * - callback_query: Inline keyboard button clicks
 *
 * Configure in Telegram with: setWebhook to /webhook/telegram
 */
final class TelegramWebhookController
{
    public function __construct(
        private readonly TelegramService $telegramService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly ?string $webhookSecret = null,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        // Validate webhook secret if configured
        if ($this->webhookSecret !== null) {
            $providedSecret = $request->headers->get('X-Telegram-Bot-Api-Secret-Token');
            if ($providedSecret !== $this->webhookSecret) {
                $this->logger->warning('Telegram webhook: invalid secret token');

                return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
        }

        // Parse update
        $content = $request->getContent();
        $update = json_decode($content, true);

        if (!\is_array($update)) {
            $this->logger->warning('Telegram webhook: invalid JSON payload');

            return new JsonResponse(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        $this->logger->debug('Telegram webhook received', ['update' => $update]);

        // Handle callback query (button click)
        if (isset($update['callback_query'])) {
            return $this->handleCallbackQuery($update['callback_query'], $update);
        }

        // Other update types can be handled here in the future
        // (message, edited_message, channel_post, etc.)

        return new JsonResponse(['ok' => true]);
    }

    private function handleCallbackQuery(array $callbackQuery, array $rawUpdate): Response
    {
        $callbackQueryId = $callbackQuery['id'] ?? '';
        $callbackData = $callbackQuery['data'] ?? '';
        $from = $callbackQuery['from'] ?? [];
        $message = $callbackQuery['message'] ?? [];
        $chatId = (string) ($message['chat']['id'] ?? '');
        $messageId = (int) ($message['message_id'] ?? 0);

        if (empty($callbackQueryId) || empty($callbackData)) {
            $this->logger->warning('Telegram callback query: missing required fields');

            return new JsonResponse(['error' => 'Missing required fields'], Response::HTTP_BAD_REQUEST);
        }

        $this->logger->info('Telegram callback query received', [
            'callback_data' => $callbackData,
            'from' => $from['username'] ?? $from['id'] ?? 'unknown',
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);

        // Dispatch event for listeners to handle
        $event = new TelegramCallbackEvent(
            $callbackQueryId,
            $callbackData,
            $chatId,
            $messageId,
            $from,
            $rawUpdate,
        );

        $this->eventDispatcher->dispatch($event);

        // Answer the callback query
        $responseText = $event->getResponseText();
        if (!$event->isHandled()) {
            $responseText = 'Acción no implementada';
        }

        $this->telegramService->answerCallbackQuery(
            $callbackQueryId,
            $responseText,
            $event->shouldShowAlert(),
        );

        return new JsonResponse(['ok' => true]);
    }
}
