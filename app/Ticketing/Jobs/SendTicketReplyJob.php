<?php

namespace App\Ticketing\Jobs;

use App\Models\SupportAiDraft;
use App\Models\SupportSavedReply;
use App\Models\SupportTicketMessage;
use App\Ticketing\Actions\DeliverMessage;
use App\Ticketing\Actions\SendLimitReached;
use App\Ticketing\Actions\TicketStateMachine;
use App\Ticketing\Enums\DeliveryStatus;
use App\Ticketing\Mailbox\Imap\ImapException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTicketReplyJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 90;

    /** @var list<int> */
    public array $backoff = [30, 180];

    public function __construct(public int $messageId) {}

    public function handle(DeliverMessage $deliver, TicketStateMachine $machine): void
    {
        $message = SupportTicketMessage::query()->with(['ticket', 'attachments', 'sender'])->find($this->messageId);

        if ($message === null || $message->delivery_status === DeliveryStatus::Sent) {
            return;
        }

        $mailbox = $message->ticket->sendingMailbox();

        if ($mailbox === null || $mailbox->isDisconnected()) {
            $this->fail(new \RuntimeException($mailbox ? 'Casella scollegata' : 'Nessuna casella collegata'));

            return;
        }

        try {
            $deliver->handle($message);
        } catch (SendLimitReached) {
            $this->release((int) now()->diffInSeconds(now()->addDay()->startOfDay()->addMinutes(5), true));

            return;
        } catch (ImapException $exception) {
            if (! $exception->authenticationFailed) {
                throw $exception;
            }

            $mailbox->disconnect($exception->getMessage());
            $this->fail($exception);

            return;
        }

        $ticket = $message->ticket;

        if ($ticket->isClosed()) {
            // closed by "reply and close" (or meanwhile): keep the status, still record the response times
            $ticket->forceFill([
                'last_outbound_at' => $message->sent_at,
                'first_response_at' => $ticket->first_response_at ?? $message->sent_at,
            ])->save();
        } else {
            $machine->reply($ticket, $message->sent_at);
        }

        if ($message->saved_reply_id) {
            SupportSavedReply::query()->find($message->saved_reply_id)?->increment('usage_count', 1, ['last_used_at' => now()]);
        }

        if ($message->ai_draft_id) {
            $this->markDraftUsed($message);
        }
    }

    public function failed(\Throwable $exception): void
    {
        SupportTicketMessage::query()->whereKey($this->messageId)->update([
            'delivery_status' => DeliveryStatus::Failed->value,
            'send_error' => mb_substr($exception->getMessage(), 0, 500),
        ]);
    }

    private function markDraftUsed(SupportTicketMessage $message): void
    {
        $draft = SupportAiDraft::query()->find($message->ai_draft_id);

        if ($draft === null) {
            return;
        }

        similar_text(mb_strtolower($draft->draft_text), mb_strtolower((string) $message->body_text), $percent);

        $draft->forceFill([
            'used' => true,
            'sent_message_id' => $message->id,
            'similarity' => round($percent / 100, 3),
        ])->save();
    }
}
