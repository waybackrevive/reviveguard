<?php

namespace App\Enums;

enum SiteType: string
{
    case WORDPRESS = 'wordpress';
    case HTML      = 'html';
    case OTHER     = 'other';

    public function label(): string
    {
        return match($this) {
            self::WORDPRESS => 'WordPress',
            self::HTML      => 'Static HTML',
            self::OTHER     => 'Other',
        };
    }
}
