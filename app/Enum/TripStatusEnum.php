<?php

namespace App\Enum;

use Kongulov\Traits\InteractWithEnum;

enum TripStatusEnum: string
{
    use InteractWithEnum;

    case STARTED = 'started';
    case ENDED = 'ended';
    case PENDING = 'pending';
    case CANCELED = 'canceled';
    case RESERVED = 'reserved';
    case PENALTY = 'penalty';
}
