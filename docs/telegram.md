# Telegram Channel

Notification channel via Telegram Bot API.

## Configuration

```yaml
# config/packages/xmon_notification.yaml
xmon_notification:
    channels:
        telegram:
            enabled: true
            bot_token: '%env(TELEGRAM_BOT_TOKEN)%'
            default_chat_id: '%env(TELEGRAM_CHAT_ID)%'
            disable_preview: false  # optional
```

### Environment Variables

```env
TELEGRAM_BOT_TOKEN=123456789:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_CHAT_ID=-1001234567890
```

## TelegramService

Main service for interacting with the Telegram Bot API.

### Injection

```php
use Xmon\NotificationBundle\Telegram\TelegramService;

public function __construct(
    private readonly TelegramService $telegramService,
) {}
```

### Available Methods

#### sendMessage

Sends a text message with Markdown support and inline buttons.

```php
$result = $this->telegramService->sendMessage(
    chatId: '-1001234567890',
    text: '*Bold title*\n\nNormal text',
    buttons: [
        TelegramButton::callback('Accept', 'accept:123'),
        TelegramButton::url('View more', 'https://example.com'),
    ],
    buttonLayout: [[0, 1]], // Both buttons in one row
);

if ($result['ok']) {
    $messageId = $result['message_id'];
}
```

#### sendPhoto

Sends a photo with caption and buttons.

```php
// With URL
$result = $this->telegramService->sendPhoto(
    chatId: '-1001234567890',
    photo: 'https://example.com/image.jpg',
    caption: '*Title*\n\nImage description',
    buttons: $buttons,
);

// With local file
$result = $this->telegramService->sendPhoto(
    chatId: '-1001234567890',
    photo: '/path/to/local/image.jpg',
    caption: 'Local image',
);
```

#### sendSticker

Sends an animated sticker.

```php
$result = $this->telegramService->sendSticker(
    chatId: '-1001234567890',
    sticker: 'CAACAgIAAxkBAAOm...', // sticker file_id
);
```

> **Note**: Sticker `file_id`s are unique per bot. See [Getting sticker file_id](#getting-sticker-file_id) section.

#### answerCallbackQuery

Responds to a button click (callback query).

```php
$this->telegramService->answerCallbackQuery(
    callbackQueryId: $event->getCallbackQueryId(),
    text: 'Action completed',  // Toast notification (max 200 chars)
    showAlert: false,          // true = modal popup
);
```

#### editMessageReplyMarkup

Edits the buttons of an existing message.

```php
// Change buttons
$this->telegramService->editMessageReplyMarkup(
    chatId: $chatId,
    messageId: $messageId,
    buttons: $newButtons,
);

// Remove buttons (disable)
$this->telegramService->editMessageReplyMarkup(
    chatId: $chatId,
    messageId: $messageId,
    buttons: [], // Empty array = no buttons
);
```

#### editMessageCaption

Edits the caption of a photo message.

```php
$this->telegramService->editMessageCaption(
    chatId: $chatId,
    messageId: $messageId,
    caption: '*New caption*',
    buttons: $buttons, // optional
);
```

#### deleteMessage

Deletes a message.

```php
$result = $this->telegramService->deleteMessage(
    chatId: $chatId,
    messageId: $messageId,
);

if (!$result['ok']) {
    // Message not found or already deleted
}
```

> **Note**: Telegram has restrictions on which messages can be deleted and when.

### Static Helpers

#### escapeMarkdown

Escapes special characters for Markdown v1.

```php
$safe = TelegramService::escapeMarkdown('Text with _underscore_ and *asterisk*');
// Result: Text with \_underscore\_ and \*asterisk\*
```

#### htmlToMarkdown

Converts basic HTML to Telegram Markdown.

```php
$markdown = TelegramService::htmlToMarkdown('<strong>Bold</strong> and <em>italic</em>');
// Result: *Bold* and _italic_
```

## TelegramButton

DTO for creating inline buttons.

### Button Types

```php
use Xmon\NotificationBundle\Telegram\TelegramButton;

// Callback button (triggers event)
$button = TelegramButton::callback(
    text: 'Publish',
    data: 'publish:123', // max 64 bytes
);

// URL button (opens link)
$button = TelegramButton::url(
    text: 'View article',
    url: 'https://example.com/article/123',
);
```

### Button Layout

The `buttonLayout` parameter defines how buttons are distributed in rows:

```php
$buttons = [
    TelegramButton::callback('A', 'a'),  // index 0
    TelegramButton::callback('B', 'b'),  // index 1
    TelegramButton::callback('C', 'c'),  // index 2
    TelegramButton::callback('D', 'd'),  // index 3
];

// All in one row: [A] [B] [C] [D]
$layout = [[0, 1, 2, 3]];

// Two rows: [A] [B] / [C] [D]
$layout = [[0, 1], [2, 3]];

// Three rows: [A] [B] / [C] / [D]
$layout = [[0, 1], [2], [3]];
```

## Webhook and Callbacks

### Configure Webhook

The bundle includes a controller to receive Telegram webhooks.

**Route**: `/webhook/telegram` (must be configured in `routes.yaml`)

```yaml
# config/routes/xmon_notification.yaml
xmon_notification_telegram_webhook:
    path: /webhook/telegram
    controller: Xmon\NotificationBundle\Controller\TelegramWebhookController
    methods: [POST]
```

**Configure in Telegram**:

```
https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://your-domain.com/webhook/telegram
```

### Handling Callbacks

Listen to `TelegramCallbackEvent` to process button clicks:

```php
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Xmon\NotificationBundle\Event\TelegramCallbackEvent;

#[AsEventListener(event: TelegramCallbackEvent::class)]
final class MyCallbackListener
{
    public function __invoke(TelegramCallbackEvent $event): void
    {
        // Parse callback_data (format "action:id")
        $parsed = $event->parseCallbackData();
        $action = $parsed['action']; // e.g.: "publish"
        $id = $parsed['id'];         // e.g.: 123

        if ($action !== 'my_action') {
            return; // Not for this listener
        }

        // Process action...

        // Respond to user
        $event->setHandled(true);
        $event->setResponseText('Action completed');
        // $event->setShowAlert(true); // For popup instead of toast
    }
}
```

### Event Properties

| Method | Description |
|--------|-------------|
| `getCallbackQueryId()` | ID for `answerCallbackQuery` |
| `getCallbackData()` | Button data (e.g.: "publish:123") |
| `parseCallbackData()` | Parses to `['action' => ..., 'id' => ...]` |
| `getChatId()` | Chat ID |
| `getMessageId()` | Message ID containing the button |
| `getFrom()` | Info about user who clicked |
| `getRawUpdate()` | Complete Telegram payload |

## Getting Sticker file_id

Sticker `file_id`s are unique per bot. To obtain them:

1. **Temporarily disable webhook**:
   ```
   https://api.telegram.org/bot{TOKEN}/deleteWebhook
   ```

2. **Send a sticker to the bot** from Telegram

3. **Query updates**:
   ```
   https://api.telegram.org/bot{TOKEN}/getUpdates
   ```

   The JSON will contain:
   ```json
   {
     "message": {
       "sticker": {
         "file_id": "CAACAgIAAxkBAAI...",
         "is_animated": true
       }
     }
   }
   ```

4. **Re-enable webhook**:
   ```
   https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://your-domain.com/webhook/telegram
   ```

## Complete Example

```php
use Xmon\NotificationBundle\Telegram\TelegramService;
use Xmon\NotificationBundle\Telegram\TelegramButton;

class NewsNotifier
{
    public function __construct(
        private readonly TelegramService $telegram,
    ) {}

    public function notifyNewArticle(Article $article): void
    {
        $buttons = [
            TelegramButton::callback('Publish', "publish:{$article->getId()}"),
            TelegramButton::callback('Discard', "discard:{$article->getId()}"),
            TelegramButton::url('Edit', $this->getEditUrl($article)),
        ];

        $caption = sprintf(
            "*%s*\n\n%s",
            TelegramService::escapeMarkdown($article->getTitle()),
            TelegramService::escapeMarkdown($article->getSummary())
        );

        $result = $this->telegram->sendPhoto(
            chatId: $this->telegram->getDefaultChatId(),
            photo: $article->getImageUrl(),
            caption: $caption,
            buttons: $buttons,
            buttonLayout: [[0, 1], [2]], // 2 buttons top, 1 bottom
        );

        if ($result['ok']) {
            // Save message_id to edit/delete later
            $article->setTelegramMessageId($result['message_id']);
        }
    }
}
```
