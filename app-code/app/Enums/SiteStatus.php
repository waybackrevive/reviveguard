<?php

namespace App\Enums;

enum SiteStatus: string
{
    case PENDING   = 'pending';
    case ACTIVE    = 'active';
    case DOWN      = 'down';
    case WARNING   = 'warning';
    case SUSPENDED = 'suspended';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'Pending',
            self::ACTIVE    => 'Active',
            self::DOWN      => 'Down',
            self::WARNING   => 'Warning',
            self::SUSPENDED => 'Suspended',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING   => 'gray',
            self::ACTIVE    => 'success',
            self::DOWN      => 'danger',
            self::WARNING   => 'warning',
            self::SUSPENDED => 'gray',
        };
    }
}
