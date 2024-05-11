<?php

namespace App\Enum;

enum SubscriptionPaymentFrequencyEnum: string
{
    case YEAR = 'year';
    case MONTH = 'month';
    case WEEK = 'week';
    case DAY = 'day';
}
