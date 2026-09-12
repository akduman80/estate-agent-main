<?php

namespace App\Enums;

enum OtpPurpose: string
{
    case EMAIL_VERIFICATION = 'email_verification';
    case LOGIN = 'login';
    case EMAIL_CHANGE = 'email_change';
    case SECURITY = 'security';
    case PASSWORD_RESET = 'password_reset';
}
