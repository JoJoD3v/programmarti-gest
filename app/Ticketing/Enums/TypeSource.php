<?php

namespace App\Ticketing\Enums;

enum TypeSource: string
{
    case Ai = 'ai';
    case Rule = 'rule';
    case Manual = 'manual';
    case Fallback = 'fallback';

    public function label(): string
    {
        return match ($this) {
            self::Ai => 'AI',
            self::Rule => 'Regola mittente',
            self::Manual => 'Manuale',
            self::Fallback => 'Non classificata',
        };
    }
}
