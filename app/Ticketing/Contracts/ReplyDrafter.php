<?php

namespace App\Ticketing\Contracts;

use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\DTO\ReplyDraft;

interface ReplyDrafter
{
    public function draft(SupportTicket $ticket, User $author, ?string $instruction = null): ReplyDraft;
}
