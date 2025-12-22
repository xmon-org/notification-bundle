<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Notification;

interface NotificationInterface
{
    /**
     * Get the notification title.
     */
    public function getTitle(): string;

    /**
     * Get the notification content/body.
     */
    public function getContent(): string;

    /**
     * Get the template name (optional, for custom rendering).
     */
    public function getTemplate(): ?string;

    /**
     * Get template context variables.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array;

    /**
     * Get the channels to send this notification through.
     *
     * @return array<string>
     */
    public function getChannels(): array;

    /**
     * Get the notification priority.
     */
    public function getPriority(): NotificationPriority;

    /**
     * Get additional metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Check if this notification should be sent asynchronously.
     */
    public function isAsync(): bool;
}
