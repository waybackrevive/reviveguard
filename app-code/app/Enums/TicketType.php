<?php

namespace App\Enums;

enum TicketType: string
{
    case GENERAL           = 'general';
    case CONTENT_EDIT      = 'content_edit';
    case EMERGENCY_RESTORE = 'emergency_restore';

    public function label(): string
    {
        return match ($this) {
            self::GENERAL           => 'General support',
            self::CONTENT_EDIT      => 'Content edit',
            self::EMERGENCY_RESTORE => 'Emergency restore',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
