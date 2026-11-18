<?php

namespace App\Ticketing\Contracts;

use App\Models\SupportTicketType;
use App\Ticketing\DTO\Classification;
use App\Ticketing\DTO\InboundEmail;
use Illuminate\Support\Collection;

interface MessageClassifier
{
    /**
     * @param  Collection<int, SupportTicketType>  $ticketTypes
     */
    public function classify(InboundEmail $email, Collection $ticketTypes): Classification;
}
