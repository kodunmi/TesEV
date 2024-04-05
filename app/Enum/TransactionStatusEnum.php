<?php

namespace App\Enum;

enum TransactionStatusEnum: string
{
    case PENDING = "pending";
    case SUCCESSFUL = "successful";
    case FAILED = "failed";
}
