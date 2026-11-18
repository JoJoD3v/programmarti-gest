<?php

namespace App\Ticketing\Enums;

enum SavedReplyScope: string
{
    case Global = 'global';
    case Personal = 'personal';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Globale',
            self::Personal => 'Personale',
        };
    }
}
