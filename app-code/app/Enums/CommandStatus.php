<?php

namespace App\Enums;

enum CommandStatus: string
{
    case PENDING   = 'pending';
    case SENT      = 'sent';
    case EXECUTING = 'executing';
    case SUCCESS   = 'success';
    case FAILED    = 'failed';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'Pending',
            self::SENT      => 'Sent',
            self::EXECUTING => 'Executing',
            self::SUCCESS   => 'Success',
            self::FAILED    => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING   => 'gray',
            self::SENT      => 'info',
            self::EXECUTING => 'warning',
            self::SUCCESS   => 'success',
            self::FAILED    => 'danger',
        };
    }
}
