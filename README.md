# Xmon Notification Bundle

[![Latest Version on Packagist](https://img.shields.io/packagist/v/xmon-org/notification-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/notification-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/xmon-org/notification-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/notification-bundle)
[![Symfony](https://img.shields.io/badge/Symfony-7.x-purple.svg?style=flat-square&logo=symfony)](https://symfony.com)
[![Total Downloads](https://img.shields.io/packagist/dt/xmon-org/notification-bundle.svg?style=flat-square)](https://packagist.org/packages/xmon-org/notification-bundle)
[![License](https://img.shields.io/packagist/l/xmon-org/notification-bundle.svg?style=flat-square)](https://github.com/xmon-org/notification-bundle/blob/main/LICENSE)


[![CI](https://github.com/xmon-org/notification-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/xmon-org/notification-bundle/actions/workflows/ci.yml)
[![semantic-release](https://img.shields.io/badge/semantic--release-conventionalcommits-e10079?logo=semantic-release)](https://github.com/semantic-release/semantic-release)


Symfony 7 bundle for multi-channel notifications (Email, Telegram, In-App).

## Features

- **Multi-channel support**: Email, Telegram (Discord, Slack planned)
- **Telegram Bot API**: Messages, photos, stickers, inline keyboards, webhooks
- **Flexible configuration**: YAML-based channel configuration
- **Event-driven**: Pre-send, sent, and failed events for extensibility
- **Template rendering**: Twig templates for customizable notifications
- **Async support**: Optional Messenger integration for background processing
- **Type-safe**: PHP 8.2+ with strict types and enums
- **Symfony 7 best practices**: DI, tagged services, compiler passes

## Documentation

- [Email Channel](docs/email.md) - Configuration, templates, events
- [Telegram Channel](docs/telegram.md) - Bot API, webhooks, inline keyboards

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

- PHP 8.2+
- Symfony 7.0+
- Symfony Mailer (for email channel)
- Symfony HttpClient (for Telegram/webhook channels)

## License

MIT License. See [LICENSE](LICENSE) file for details.

## Development Status

**Phase 1**: Core + Email Channel ✅
- Channel architecture
- Email channel with Symfony Mailer
- Event system
- Template rendering

**Phase 2**: Telegram Channel ✅
- TelegramService with full Bot API support
- Inline keyboards with callback handling
- Webhook controller for updates
- Photo, message, sticker support

**Phase 3 (Planned)**: In-App Notifications (Sonata Admin)
**Phase 4 (Planned)**: Messenger Async Support

## Contributing

Contributions welcome! See [CONTRIBUTING.md](CONTRIBUTING.md) for:

- Development setup
- Code standards (PHP-CS-Fixer, PHPStan)
- Git hooks and commit conventions
- Pull request process
