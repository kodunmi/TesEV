<?php

namespace App\Enum;

enum PaymentTypeEnum: string
{
    case CARD = "card";
    case SUBSCRIPTION = "subscription";
}


enum TripPaymentTypeEnum: string
{
    case OTHERS = "others";
    case SUBSCRIPTION = "subscription";
}
