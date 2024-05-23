<?php

namespace App\Enum;

use Kongulov\Traits\InteractWithEnum;

enum TripTransactionTypeEnum: string
{
    use InteractWithEnum;
    case TRIP = "trip";
    case EXTRA_TIME = "extra_time";
}
