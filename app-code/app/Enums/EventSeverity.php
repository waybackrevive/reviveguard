<?php

namespace App\Enums;

enum EventSeverity: string
{
    case SUCCESS  = 'success';
    case INFO     = 'info';
    case WARNING  = 'warning';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match($this) {
            self::SUCCESS  => 'Success',
            self::INFO     => 'Info',
            self::WARNING  => 'Warning',
            self::CRITICAL => 'Critical',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::SUCCESS  => 'success',
            self::INFO     => 'info',
            self::WARNING  => 'warning',
            self::CRITICAL => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::SUCCESS  => 'heroicon-o-check-circle',
            self::INFO     => 'heroicon-o-information-circle',
            self::WARNING  => 'heroicon-o-exclamation-triangle',
            self::CRITICAL => 'heroicon-o-x-circle',
        };
    }
}
