<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Telegram;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Telegram Bot API service for advanced messaging features.
 *
 * Provides methods for:
 * - Sending messages with inline keyboards
 * - Sending photos with captions and buttons
 * - Answering callback queries (button clicks)
 * - Editing message keyboards
 */
final class TelegramService
{
    private const API_BASE = 'https://api.telegram.org/bot%s/%s';

    public function __construct(
        private readonly ?HttpClientInterface $httpClient,
        private readonly array $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Check if Telegram is properly configured.
     */
    public function isConfigured(): bool
    {
        return $this->httpClient !== null
            && ($this->config['enabled'] ?? false)
            && !empty($this->config['bot_token'] ?? '');
    }

    /**
     * Get the default chat ID from configuration.
     */
    public function getDefaultChatId(): ?string
    {
        return $this->config['default_chat_id'] ?? null;
    }

    /**
     * Send a text message with optional inline keyboard.
     *
     * @param string                $chatId       Chat ID to send to
     * @param string                $text         Message text (Markdown supported)
     * @param array<TelegramButton> $buttons      Optional buttons
     * @param array<array<int>>     $buttonLayout Layout matrix (e.g., [[0,1],[2]] = 2 buttons row 1, 1 button row 2)
     *
     * @return array{ok: bool, message_id?: int, error?: string}
     */
    public function sendMessage(
        string $chatId,
        string $text,
        array $buttons = [],
        array $buttonLayout = [],
    ): array {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => $this->config['disable_preview'] ?? false,
        ];

        if (!empty($buttons)) {
            $payload['reply_markup'] = $this->buildInlineKeyboard($buttons, $buttonLayout);
        }

        return $this->callApi('sendMessage', $payload);
    }

    /**
     * Send a photo with caption and optional inline keyboard.
     *
     * @param string                $chatId       Chat ID to send to
     * @param string                $photo        Photo URL or file_id
     * @param string                $caption      Caption text (max 1024 chars, Markdown supported)
     * @param array<TelegramButton> $buttons      Optional buttons
     * @param array<array<int>>     $buttonLayout Layout matrix
     *
     * @return array{ok: bool, message_id?: int, error?: string}
     */
    public function sendPhoto(
        string $chatId,
        string $photo,
        string $caption = '',
        array $buttons = [],
        array $buttonLayout = [],
    ): array {
        $payload = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'parse_mode' => 'Markdown',
        ];

        if (!empty($caption)) {
            // Telegram caption limit is 1024 chars
            $payload['caption'] = mb_substr($caption, 0, 1024);
        }

        if (!empty($buttons)) {
            $payload['reply_markup'] = $this->buildInlineKeyboard($buttons, $buttonLayout);
        }

        return $this->callApi('sendPhoto', $payload);
    }

    /**
     * Answer a callback query (button click).
     *
     * Shows a toast notification or alert to the user.
     *
     * @param string $callbackQueryId The callback_query id from the update
     * @param string $text            Text to show (max 200 chars)
     * @param bool   $showAlert       Show as alert popup instead of toast
     */
    public function answerCallbackQuery(
        string $callbackQueryId,
        string $text = '',
        bool $showAlert = false,
    ): array {
        return $this->callApi('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => mb_substr($text, 0, 200),
            'show_alert' => $showAlert,
        ]);
    }

    /**
     * Edit the inline keyboard of a message.
     *
     * @param string                $chatId       Chat ID
     * @param int                   $messageId    Message ID to edit
     * @param array<TelegramButton> $buttons      New buttons (empty to remove keyboard)
     * @param array<array<int>>     $buttonLayout Layout matrix
     */
    public function editMessageReplyMarkup(
        string $chatId,
        int $messageId,
        array $buttons = [],
        array $buttonLayout = [],
    ): array {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ];

        if (!empty($buttons)) {
            $payload['reply_markup'] = $this->buildInlineKeyboard($buttons, $buttonLayout);
        } else {
            // Remove keyboard by passing empty inline_keyboard
            $payload['reply_markup'] = json_encode(['inline_keyboard' => []]);
        }

        return $this->callApi('editMessageReplyMarkup', $payload);
    }

    /**
     * Edit the caption of a photo message.
     *
     * @param string                $chatId       Chat ID
     * @param int                   $messageId    Message ID to edit
     * @param string                $caption      New caption
     * @param array<TelegramButton> $buttons      Optional new buttons
     * @param array<array<int>>     $buttonLayout Layout matrix
     */
    public function editMessageCaption(
        string $chatId,
        int $messageId,
        string $caption,
        array $buttons = [],
        array $buttonLayout = [],
    ): array {
        $payload = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'caption' => mb_substr($caption, 0, 1024),
            'parse_mode' => 'Markdown',
        ];

        if (!empty($buttons)) {
            $payload['reply_markup'] = $this->buildInlineKeyboard($buttons, $buttonLayout);
        }

        return $this->callApi('editMessageCaption', $payload);
    }

    /**
     * Build inline keyboard from buttons and layout.
     *
     * @param array<TelegramButton> $buttons      Flat array of buttons
     * @param array<array<int>>     $buttonLayout Matrix defining rows (e.g., [[0,1],[2]])
     *                                            If empty, all buttons in one row
     */
    private function buildInlineKeyboard(array $buttons, array $buttonLayout = []): string
    {
        if (empty($buttons)) {
            return json_encode(['inline_keyboard' => []]);
        }

        $keyboard = [];

        if (empty($buttonLayout)) {
            // Default: all buttons in one row
            $row = [];
            foreach ($buttons as $button) {
                $row[] = $button->toArray();
            }
            $keyboard[] = $row;
        } else {
            // Use layout matrix
            foreach ($buttonLayout as $rowIndices) {
                $row = [];
                foreach ($rowIndices as $index) {
                    if (isset($buttons[$index])) {
                        $row[] = $buttons[$index]->toArray();
                    }
                }
                if (!empty($row)) {
                    $keyboard[] = $row;
                }
            }
        }

        return json_encode(['inline_keyboard' => $keyboard]);
    }

    /**
     * Make an API call to Telegram.
     *
     * @return array{ok: bool, message_id?: int, error?: string, result?: array}
     */
    private function callApi(string $method, array $payload): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Telegram is not configured'];
        }

        $url = \sprintf(self::API_BASE, $this->config['bot_token'], $method);

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => $payload,
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray(false);

            if ($statusCode === 200 && ($content['ok'] ?? false)) {
                $this->logger->debug('Telegram API success', [
                    'method' => $method,
                    'result' => $content['result'] ?? null,
                ]);

                return [
                    'ok' => true,
                    'message_id' => $content['result']['message_id'] ?? null,
                    'result' => $content['result'] ?? [],
                ];
            }

            $error = $content['description'] ?? 'Unknown Telegram API error';
            $this->logger->error('Telegram API error', [
                'method' => $method,
                'status_code' => $statusCode,
                'error' => $error,
            ]);

            return ['ok' => false, 'error' => $error];
        } catch (\Throwable $e) {
            $this->logger->error('Telegram API exception', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
