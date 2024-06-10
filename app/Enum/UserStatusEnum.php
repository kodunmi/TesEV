<?php

namespace App\Enum;

use Kongulov\Traits\InteractWithEnum;

enum UserStatusEnum: string
{
    use InteractWithEnum;
    case DEACTIVATED = 'deactivated';
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
}
