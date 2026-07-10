<?php

namespace App\Enums;

enum CommandType: string
{
    case RUN_BACKUP        = 'run_backup';
    case RUN_WP_UPDATES    = 'run_wp_updates';
    case ROLLBACK_RESTORE  = 'rollback_restore';
    case RUN_MALWARE_SCAN  = 'run_malware_scan';

    public function label(): string
    {
        return match($this) {
            self::RUN_BACKUP        => 'Run Backup',
            self::RUN_WP_UPDATES    => 'Run WP Updates',
            self::ROLLBACK_RESTORE  => 'Rollback Restore',
            self::RUN_MALWARE_SCAN  => 'Run Malware Scan',
        };
    }
}
