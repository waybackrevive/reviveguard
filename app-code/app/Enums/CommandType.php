<?php

namespace App\Enums;

enum CommandType: string
{
    case RUN_BACKUP     = 'run_backup';
    case RUN_WP_UPDATES = 'run_wp_updates';

    public function label(): string
    {
        return match($this) {
            self::RUN_BACKUP     => 'Run Backup',
            self::RUN_WP_UPDATES => 'Run WP Updates',
        };
    }
}
