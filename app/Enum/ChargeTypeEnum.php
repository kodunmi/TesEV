<?php

namespace App\Enum;

enum ChargeTypeEnum: string
{
    case WALLET_FUND = "wallet.fund";
    case TRIP_FUND = "trip.fund";
}
