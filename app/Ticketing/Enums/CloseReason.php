<?php

namespace App\Ticketing\Enums;

enum CloseReason: string
{
    case Manual = 'manual';
    case Auto = 'auto';
    case Spam = 'spam';
    case NotRelevant = 'not_relevant';
    case Merged = 'merged';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Chiuso a mano',
            self::Auto => 'Chiusura automatica',
            self::Spam => 'Spam',
            self::NotRelevant => 'Non pertinente',
            self::Merged => 'Unito ad altro ticket',
        };
    }
}
