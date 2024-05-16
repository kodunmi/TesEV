<?php

namespace App\Enum;

enum NotificationTypeEnum: string
{
    case EXPIRED_SUBSCRIPTION = 'expired_subscription';
    case BATTERY_LEVEL = 'battery_level';
    case KYC_UPDATED = 'kyc_updated';
    case WALLET_FUND = "wallet.fund";
    case TRIP_BOOKED = "trip.booked";
    case WALLET_FUND_FAILED = 'wallet.fund.failed';
}
