<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Notification;

enum NotificationPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';
}
