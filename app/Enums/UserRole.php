<?php

/** Created by ramazanorak, Date: 26.07.2026 */

namespace App\Enums;

enum UserRole: string
{
    case SUPERVISOR = 'supervisor'; // Admin Paneli (/admin)
    case AGENT = 'agent';      // Emlakçı / Müşteri Paneli (/portal)
    case CONSULTANT = 'consultant'; // Agent'a bağlı danışman (/portal)
    case INDIVIDUAL = 'individual'; // Bireysel / Gayrimenkul sahibi (/portal)
    case EDITOR = 'editor';     // Haber & Blogger Paneli (/editor)

    public function getRedirectPath(): string
    {
        return match ($this) {
            self::SUPERVISOR => '/admin',
            self::AGENT => '/portal',
            self::CONSULTANT => '/portal',
            self::INDIVIDUAL => '/portal',
            self::EDITOR => '/editor',
        };
    }
}
