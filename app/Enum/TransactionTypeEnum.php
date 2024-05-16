<?php

namespace App\Enum;

enum TransactionTypeEnum: string
{
    case SUBSCRIPTION = 'subscription';
    case TRIP = "trip";
    case WALLET = "wallet";
}
