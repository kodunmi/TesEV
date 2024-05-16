<?php

namespace App\Enum;

use Kongulov\Traits\InteractWithEnum;

enum PaymentTypeEnum: string
{
    use InteractWithEnum;

    case CARD = "card";
    case SUBSCRIPTION = "subscription";
    case WALLET = "wallet";
}
