<?php

namespace App\Enums;

enum AccountStatus: string
{
    case PENDING_EMAIL = 'pending_email';
    case PENDING_APPROVAL = 'pending_approval';
    case ACTIVE = 'active';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';
}
