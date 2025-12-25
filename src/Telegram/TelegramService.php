<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Telegram;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
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
     * Get the configured chat IDs.
     *
     * @return array<string>
     */
    public function getChatIds(): array
    {
        return array_map('strval', $this->config['chat_ids'] ?? []);
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
     * @param string                $photo        Photo URL, file_id, or local file path
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
        // Detect if photo is a local file path
        $isLocalFile = $this->isLocalFilePath($photo);

        if ($isLocalFile) {
            return $this->sendPhotoAsFile($chatId, $photo, $caption, $buttons, $buttonLayout);
        }

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
     * Send a photo by uploading the file directly (multipart/form-data).
     *
     * @param string                $chatId       Chat ID to send to
     * @param string                $filePath     Local file path
     * @param string                $caption      Caption text (max 1024 chars, Markdown supported)
     * @param array<TelegramButton> $buttons      Optional buttons
     * @param array<array<int>>     $buttonLayout Layout matrix
     *
     * @return array{ok: bool, message_id?: int, error?: string}
     */
    private function sendPhotoAsFile(
        string $chatId,
        string $filePath,
        string $caption = '',
        array $buttons = [],
        array $buttonLayout = [],
    ): array {
        if (!file_exists($filePath)) {
            return ['ok' => false, 'error' => \sprintf('File not found: %s', $filePath)];
        }

        $formFields = [
            'chat_id' => $chatId,
            'parse_mode' => 'Markdown',
            'photo' => DataPart::fromPath($filePath),
        ];

        if (!empty($caption)) {
            $formFields['caption'] = mb_substr($caption, 0, 1024);
        }

        if (!empty($buttons)) {
            $formFields['reply_markup'] = $this->buildInlineKeyboard($buttons, $buttonLayout);
        }

        return $this->callApiMultipart('sendPhoto', $formFields);
    }

    /**
     * Check if the given string is a local file path.
     */
    private function isLocalFilePath(string $path): bool
    {
        // Unix absolute path or Windows absolute path
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            return file_exists($path);
        }

        return false;
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
     * Delete a message.
     *
     * Note: Telegram has restrictions on deleting messages:
     * - Bots can delete outgoing messages in private chats, groups, and supergroups
     * - Bots can delete incoming messages in private chats
     * - Bots with can_delete_messages permission can delete any message in groups/supergroups
     * - Messages older than 48 hours cannot be deleted in supergroups
     *
     * @param string $chatId    Chat ID
     * @param int    $messageId Message ID to delete
     *
     * @return array{ok: bool, error?: string}
     */
    public function deleteMessage(string $chatId, int $messageId): array
    {
        return $this->callApi('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    /**
     * Send an animated sticker.
     *
     * @param string $chatId  Chat ID to send to
     * @param string $sticker Sticker file_id (unique per bot)
     *
     * @return array{ok: bool, message_id?: int, error?: string}
     */
    public function sendSticker(string $chatId, string $sticker): array
    {
        return $this->callApi('sendSticker', [
            'chat_id' => $chatId,
            'sticker' => $sticker,
        ]);
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

            return $this->processResponse($method, $response);
        } catch (\Throwable $e) {
            $this->logger->error('Telegram API exception', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Make an API call to Telegram with multipart/form-data (for file uploads).
     *
     * @param array<string, mixed> $formFields Fields including DataPart for files
     *
     * @return array{ok: bool, message_id?: int, error?: string, result?: array}
     */
    private function callApiMultipart(string $method, array $formFields): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'error' => 'Telegram is not configured'];
        }

        $url = \sprintf(self::API_BASE, $this->config['bot_token'], $method);

        try {
            $formData = new FormDataPart($formFields);

            $response = $this->httpClient->request('POST', $url, [
                'headers' => $formData->getPreparedHeaders()->toArray(),
                'body' => $formData->bodyToIterable(),
            ]);

            return $this->processResponse($method, $response);
        } catch (\Throwable $e) {
            $this->logger->error('Telegram API exception (multipart)', [
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Process Telegram API response.
     *
     * @return array{ok: bool, message_id?: int, error?: string, result?: array}
     */
    private function processResponse(string $method, mixed $response): array
    {
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
    }

    /**
     * Convert HTML to Telegram Markdown v1 format.
     *
     * Converts common HTML tags to their Markdown equivalents:
     * - <strong>, <b> → *bold*
     * - <em>, <i> → _italic_
     * - <p> → double newline (paragraph break)
     * - <br> → single newline
     * - Other tags → stripped
     *
     * @param string $html HTML content to convert
     *
     * @return string Telegram-compatible Markdown text
     */
    public static function htmlToMarkdown(string $html): string
    {
        // Convert <strong> and <b> to *bold*
        $text = preg_replace('/<(strong|b)>(.*?)<\/\1>/is', '*$2*', $html);

        // Convert <em> and <i> to _italic_
        $text = preg_replace('/<(em|i)>(.*?)<\/\1>/is', '_$2_', $text);

        // Convert </p> to double newline (paragraph break)
        $text = preg_replace('/<\/p>\s*/i', "\n\n", $text);

        // Convert <br> to single newline
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

        // Remove all remaining HTML tags
        $text = strip_tags($text);

        // Clean up excessive whitespace
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Escape special characters for Telegram Markdown v1.
     *
     * Escapes: _ * ` [
     *
     * @param string $text Plain text to escape
     *
     * @return string Escaped text safe for Markdown
     */
    public static function escapeMarkdown(string $text): string
    {
        return preg_replace('/([_*`\[])/', '\\\\$1', $text);
    }
}
