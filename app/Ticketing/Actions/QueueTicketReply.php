<?php

namespace App\Ticketing\Actions;

use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Ticketing\Enums\CloseReason;
use App\Ticketing\Enums\DeliveryStatus;
use App\Ticketing\Enums\MessageDirection;
use App\Ticketing\Enums\MessageType;
use App\Ticketing\Jobs\SendTicketReplyJob;
use App\Ticketing\Support\TicketingSettings;
use App\Ticketing\Support\TicketSubject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Records an outbound reply as "sending" and queues the delivery. The recipient is always the ticket's customer,
 * never something typed in a form. "Reply and close" closes the ticket right away, without waiting for the delivery.
 */
class QueueTicketReply
{
    public function __construct(
        private readonly TicketingSettings $settings,
        private readonly TicketStateMachine $machine,
    ) {}

    /**
     * @param  list<UploadedFile>  $attachments
     */
    public function handle(
        SupportTicket $ticket,
        User $author,
        string $body,
        array $attachments = [],
        ?int $savedReplyId = null,
        ?int $aiDraftId = null,
        bool $closeAfter = false,
    ): SupportTicketMessage {
        if ($ticket->isClosed()) {
            throw new InvalidArgumentException('Il ticket è chiuso: non si può rispondere.');
        }

        $lastInbound = $ticket->lastInboundMessage();
        $mailbox = $ticket->sendingMailbox();
        $domain = $mailbox ? Str::after($mailbox->address, '@') : config('ticketing.mailbox.message_id_domain');
        $messageId = '<'.Str::uuid().'@'.$domain.'>';

        $references = array_values(array_filter(array_unique([
            ...preg_split('/\s+/', trim((string) $lastInbound?->references), -1, PREG_SPLIT_NO_EMPTY),
            $lastInbound?->message_id_header,
        ])));

        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'direction' => MessageDirection::Out,
            'type' => MessageType::Reply,
            'delivery_status' => DeliveryStatus::Sending,
            'message_id_header' => $messageId,
            'in_reply_to' => $lastInbound?->message_id_header,
            'references' => $references === [] ? null : implode(' ', $references),
            'from_email' => $mailbox?->address,
            'to_email' => $ticket->customer_email,
            'subject' => TicketSubject::forReply($lastInbound?->subject ?? $ticket->subject, $ticket->public_id),
            'body_text' => $body,
            'sent_by' => $author->id,
            'saved_reply_id' => $savedReplyId,
            'ai_draft_id' => $aiDraftId,
        ]);

        foreach ($attachments as $file) {
            $path = $file->storeAs(
                config('ticketing.mailbox.storage_path')."/outbound/{$message->id}",
                Str::random(8).'-'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension(),
                config('ticketing.mailbox.storage_disk'),
            );

            SupportTicketAttachment::create([
                'support_ticket_message_id' => $message->id,
                'filename' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'disk' => config('ticketing.mailbox.storage_disk'),
                'path' => $path,
            ]);
        }

        $ticket->forceFill(['reply_draft' => null])->save();

        if ($closeAfter) {
            $this->machine->close($ticket, CloseReason::Manual, $author);
        }

        SendTicketReplyJob::dispatch($message->id);

        return $message;
    }

    public function signatureFor(User $author): string
    {
        $signature = trim((string) $this->settings->get('reply_signature'));

        return str_replace('{dipendente_nome}', $author->first_name, $signature);
    }
}
