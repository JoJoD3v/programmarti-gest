<?php

namespace App\Ticketing\Actions;

use App\Models\SupportAiDraft;
use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\Contracts\ReplyDrafter;
use App\Ticketing\Enums\TicketStatus;
use InvalidArgumentException;

class GenerateReplyDraft
{
    public function __construct(private readonly ReplyDrafter $drafter) {}

    public function handle(SupportTicket $ticket, User $author, ?string $instruction = null, bool $pregenerated = false): SupportAiDraft
    {
        $based = $ticket->lastInboundMessage();

        if ($ticket->status !== TicketStatus::Open || $based === null || $ticket->lastMessage()?->isInbound() === false) {
            throw new InvalidArgumentException('La bozza si genera quando l\'ultimo messaggio è del cliente.');
        }

        $draft = $this->drafter->draft($ticket, $author, $instruction);

        return SupportAiDraft::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $author->id,
            'based_on_message_id' => $based->id,
            'instruction' => $instruction,
            'model' => $draft->model,
            'prompt_version' => $draft->promptVersion,
            'draft_text' => $draft->text,
            'input_tokens' => $draft->inputTokens,
            'output_tokens' => $draft->outputTokens,
            'generated_at' => now(),
            'pregenerated' => $pregenerated,
        ]);
    }
}
