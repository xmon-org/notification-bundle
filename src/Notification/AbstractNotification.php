<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Notification;

abstract class AbstractNotification implements NotificationInterface
{
    public function __construct(
        protected readonly string $title,
        protected readonly string $content,
        protected readonly ?string $template = null,
        protected readonly array $context = [],
        protected readonly array $channels = [],
        protected readonly NotificationPriority $priority = NotificationPriority::Normal,
        protected readonly array $metadata = [],
        protected readonly bool $async = false,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function getPriority(): NotificationPriority
    {
        return $this->priority;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }
}
