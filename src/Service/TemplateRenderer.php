<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Service;

use Twig\Environment;
use Xmon\NotificationBundle\Notification\NotificationInterface;

/**
 * Renders notification templates using Twig.
 */
final readonly class TemplateRenderer
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    /**
     * Render a notification template.
     */
    public function render(NotificationInterface $notification, string $format = 'html'): string
    {
        $template = $notification->getTemplate();

        // If no custom template, use default
        if ($template === null) {
            $template = \sprintf('@XmonNotification/email/default.%s.twig', $format);
        }

        // If template doesn't have extension, add format
        if (!str_contains($template, '.twig')) {
            $template .= \sprintf('.%s.twig', $format);
        }

        return $this->twig->render($template, array_merge(
            $notification->getContext(),
            [
                'notification' => $notification,
                'title' => $notification->getTitle(),
                'content' => $notification->getContent(),
            ]
        ));
    }

    /**
     * Check if Twig is available.
     */
    public function isAvailable(): bool
    {
        return class_exists(Environment::class);
    }
}
