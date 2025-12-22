<?php

declare(strict_types=1);

namespace Xmon\NotificationBundle\Result;

enum ResultStatus: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Queued = 'queued';
}
