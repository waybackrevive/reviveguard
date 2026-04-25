<?php

namespace App\Enums;

enum BackupStatus: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED  = 'failed';
    case EXPIRED = 'expired';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::RUNNING => 'Running',
            self::SUCCESS => 'Success',
            self::FAILED  => 'Failed',
            self::EXPIRED => 'Expired',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::RUNNING => 'info',
            self::SUCCESS => 'success',
            self::FAILED  => 'danger',
            self::EXPIRED => 'gray',
        };
    }
}
