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
    case TRIP_ENDED = 'trip.ended';
    case TRIP_STARTED = 'trip.started';
    case TRIP_STARTING_SOON = 'trip.starting.soon';
    case TRIP_ENDING_SOON = 'trip.ending.soon';
    case TRIP_PENALTY_TIME = 'trip.penalty.time';
}
