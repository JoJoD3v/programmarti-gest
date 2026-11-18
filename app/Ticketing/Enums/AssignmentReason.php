<?php

namespace App\Ticketing\Enums;

enum AssignmentReason: string
{
    case Auto = 'auto';
    case Manual = 'manual';
    case Reassign = 'reassign';
    case Bulk = 'bulk';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Automatica',
            self::Manual => 'Manuale',
            self::Reassign => 'Riassegnazione',
            self::Bulk => 'Riassegnazione massiva',
            self::Disabled => 'Dipendente disabilitato',
        };
    }
}
