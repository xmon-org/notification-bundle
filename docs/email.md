# Email Channel

Notification channel via email using Symfony Mailer.

## Configuration

```yaml
# config/packages/xmon_notification.yaml
xmon_notification:
    channels:
        email:
            enabled: true
            from: 'noreply@example.com'
            from_name: 'My Application'  # optional
```

### Requirements

- Symfony Mailer configured (`symfony/mailer`)
- Email transport configured in `.env`:

```env
MAILER_DSN=smtp://user:pass@smtp.example.com:587
```

## Basic Usage

### With NotificationService

```php
use Xmon\NotificationBundle\Notification\SimpleNotification;
use Xmon\NotificationBundle\Recipient\Recipient;
use Xmon\NotificationBundle\Service\NotificationService;

class WelcomeService
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function sendWelcome(User $user): void
    {
        $notification = new SimpleNotification(
            title: 'Welcome to our platform',
            content: "Hello {$user->getName()},\n\nThank you for signing up.",
            channels: ['email'],
        );

        $recipient = new Recipient(
            email: $user->getEmail(),
        );

        $results = $this->notificationService->send($notification, $recipient);

        foreach ($results as $result) {
            if ($result->isSuccess()) {
                // Email sent
            } else {
                // Error: $result->getMessage()
            }
        }
    }
}
```

### With Twig Template

```php
$notification = new SimpleNotification(
    title: 'Order Confirmed',
    content: '', // Ignored when template is provided
    template: 'emails/order_confirmation',
    context: [
        'order' => $order,
        'user' => $user,
    ],
    channels: ['email'],
);
```

**Template** (`templates/emails/order_confirmation.html.twig`):

```twig
<h1>Order #{{ order.id }} Confirmed</h1>

<p>Hello {{ user.name }},</p>

<p>Your order has been confirmed and is being processed.</p>

<table>
    {% for item in order.items %}
    <tr>
        <td>{{ item.name }}</td>
        <td>{{ item.quantity }}</td>
        <td>{{ item.price|number_format(2) }} EUR</td>
    </tr>
    {% endfor %}
</table>

<p><strong>Total: {{ order.total|number_format(2) }} EUR</strong></p>
```

## Priorities

```php
use Xmon\NotificationBundle\Notification\NotificationPriority;

$notification = new SimpleNotification(
    title: 'Security Alert',
    content: 'A suspicious login attempt has been detected.',
    priority: NotificationPriority::Urgent,
    channels: ['email'],
);
```

Available priorities:

| Priority | Description |
|----------|-------------|
| `Low` | Non-urgent notifications |
| `Normal` | Default priority |
| `High` | Important notifications |
| `Urgent` | Critical alerts |

## Multiple Channels

Send the same notification via email and Telegram:

```php
$notification = new SimpleNotification(
    title: 'New Task Assigned',
    content: 'Task #456 has been assigned to you',
    channels: ['email', 'telegram'],
);

$recipient = new Recipient(
    email: $user->getEmail(),
    telegramChatId: $user->getTelegramChatId(),
);

// Will be sent through both channels
$results = $this->notificationService->send($notification, $recipient);
```

## Events

Subscribe to lifecycle events:

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Xmon\NotificationBundle\Event\NotificationPreSendEvent;
use Xmon\NotificationBundle\Event\NotificationSentEvent;
use Xmon\NotificationBundle\Event\NotificationFailedEvent;

class NotificationSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            NotificationPreSendEvent::class => 'onPreSend',
            NotificationSentEvent::class => 'onSent',
            NotificationFailedEvent::class => 'onFailed',
        ];
    }

    public function onPreSend(NotificationPreSendEvent $event): void
    {
        // Modify notification before sending
        // Or cancel the send:
        // $event->cancel();
    }

    public function onSent(NotificationSentEvent $event): void
    {
        // Log success
    }

    public function onFailed(NotificationFailedEvent $event): void
    {
        // Log error, retry, etc.
    }
}
```

## Troubleshooting

### Email Not Sending

1. Verify `MAILER_DSN` configuration in `.env`
2. Verify channel is enabled (`enabled: true`)
3. Verify `from` is configured
4. Check Symfony Mailer logs

### Recipient Without Email

If the `Recipient` has no email configured, the channel will return a `NotificationResult` with `Failed` status and message "Recipient has no email address".

```php
$recipient = new Recipient(
    email: null, // No email
    telegramChatId: '123456',
);

// Will only be sent via Telegram, email will fail silently
```
