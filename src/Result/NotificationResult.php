<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Result;

/**
 * Immutable result of a notification send operation.
 */
final readonly class NotificationResult
{
    public function __construct(
        public string $channel,
        public ResultStatus $status,
        public ?string $message = null,
        public array $metadata = [],
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->status === ResultStatus::Success;
    }

    public function isFailed(): bool
    {
        return $this->status === ResultStatus::Failed;
    }

    public function isQueued(): bool
    {
        return $this->status === ResultStatus::Queued;
    }
}
