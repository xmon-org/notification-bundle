# Xmon Notification Bundle

Symfony 7 bundle for multi-channel notifications (Email, Telegram, In-App).

## Features

- **Multi-channel support**: Email, Telegram, In-App (Discord, Slack planned)
- **Flexible configuration**: YAML-based channel configuration
- **Event-driven**: Pre-send, sent, and failed events for extensibility
- **Template rendering**: Twig templates for customizable notifications
- **Async support**: Optional Messenger integration for background processing
- **Type-safe**: PHP 8.3+ with strict types and enums
- **Symfony 7 best practices**: DI, tagged services, compiler passes

## Installation

```bash
composer require xmon-org/notification-bundle
```

## Configuration

```yaml
# config/packages/xmon_notification.yaml
xmon_notification:
    channels:
        email:
            enabled: true
            from: 'noreply@example.com'
            from_name: 'My App'

        telegram:
            enabled: true
            bot_token: '%env(TELEGRAM_BOT_TOKEN)%'
            default_chat_id: '%env(TELEGRAM_CHAT_ID)%'

        in_app:
            enabled: true
            storage: 'session'  # session | doctrine
            max_notifications: 50

    messenger:
        enabled: false
        transport: 'async'

    defaults:
        channels: ['email']
        priority: 'normal'
```

## Usage

### Basic Example

```php
use Xmon\NotificationBundle\Notification\SimpleNotification;
use Xmon\NotificationBundle\Recipient\Recipient;
use Xmon\NotificationBundle\Service\NotificationService;

class MyService
{
    public function __construct(
        private NotificationService $notificationService,
    ) {}

    public function sendWelcomeEmail(User $user): void
    {
        $notification = new SimpleNotification(
            title: 'Welcome!',
            content: 'Thanks for joining our platform.',
            channels: ['email', 'telegram'],
        );

        $recipient = new Recipient(
            email: $user->getEmail(),
            telegramChatId: $user->getTelegramChatId(),
        );

        $results = $this->notificationService->send($notification, $recipient);

        foreach ($results as $result) {
            if ($result->isSuccess()) {
                // Handle success
            }
        }
    }
}
```

### With Custom Template

```php
$notification = new SimpleNotification(
    title: 'Order Confirmed',
    content: 'Your order #123 has been confirmed.',
    template: 'emails/order_confirmation',
    context: ['order' => $order],
    channels: ['email'],
);
```

### Priority Levels

```php
use Xmon\NotificationBundle\Notification\NotificationPriority;

$notification = new SimpleNotification(
    title: 'Critical Alert',
    content: 'System error detected!',
    priority: NotificationPriority::Urgent,
);
```

## Events

Subscribe to notification events for custom logic:

```php
use Xmon\NotificationBundle\Event\NotificationPreSendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NotificationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            NotificationPreSendEvent::class => 'onPreSend',
        ];
    }

    public function onPreSend(NotificationPreSendEvent $event): void
    {
        // Modify notification or cancel sending
        if ($this->shouldCancel($event->notification)) {
            $event->cancel();
        }
    }
}
```

## Requirements

- PHP 8.3+
- Symfony 7.0+
- Symfony Mailer (for email channel)
- Symfony HttpClient (for Telegram/webhook channels)

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Development Status

**Phase 1 (Current)**: Core + Email Channel ✅
- Channel architecture
- Email channel with Symfony Mailer
- Event system
- Template rendering

**Phase 2 (Planned)**: Telegram Channel
**Phase 3 (Planned)**: In-App Notifications (Sonata Admin)
**Phase 4 (Planned)**: Messenger Async Support

## Contributing

Contributions welcome! Please follow Symfony coding standards and include tests.
