<?php

namespace App\Ticketing\Fakes;

use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\Contracts\ReplyDrafter;
use App\Ticketing\DTO\ReplyDraft;

class FakeReplyDrafter implements ReplyDrafter
{
    public string $text = "Buongiorno,\nla ringraziamo per averci scritto.\nCordiali saluti";

    public int $calls = 0;

    public ?\Throwable $failWith = null;

    public function draft(SupportTicket $ticket, User $author, ?string $instruction = null): ReplyDraft
    {
        $this->calls++;

        if ($this->failWith) {
            throw $this->failWith;
        }

        return new ReplyDraft($this->text, 'fake-model', 'v1', 100, 50);
    }
}
