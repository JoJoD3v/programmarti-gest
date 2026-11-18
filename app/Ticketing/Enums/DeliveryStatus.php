<?php

namespace App\Ticketing\Enums;

enum DeliveryStatus: string
{
    case Received = 'received';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Ricevuto',
            self::Sending => 'In invio',
            self::Sent => 'Inviato',
            self::Failed => 'Non inviato',
        };
    }
}
