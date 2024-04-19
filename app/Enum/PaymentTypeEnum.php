<?php

namespace App\Enum;

enum PaymentTypeEnum: string
{
    case CARD = "card";
    case SUBSCRIPTION = "subscription";
}
