<?php

namespace App\Ticketing\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Aperto',
            self::Pending => 'In attesa del cliente',
            self::Closed => 'Chiuso',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'bg-amber-100 text-amber-800',
            self::Pending => 'bg-blue-100 text-blue-800',
            self::Closed => 'bg-slate-200 text-slate-700',
        };
    }
}
