<?php

namespace App\Ticketing\Actions;

use App\Models\Client;
use App\Models\Mailbox;
use App\Models\SupportTicket;
use App\Models\SupportTicketAttachment;
use App\Models\SupportTicketMessage;
use App\Ticketing\DTO\InboundEmail;
use App\Ticketing\DTO\MatchResult;
use App\Ticketing\Enums\AssignmentReason;
use App\Ticketing\Enums\DeliveryStatus;
use App\Ticketing\Enums\MessageDirection;
use App\Ticketing\Enums\MessageType;
use App\Ticketing\Enums\TicketStatus;
use App\Ticketing\Events\CustomerMessageReceived;
use App\Ticketing\Jobs\ApplyMailboxLabelJob;
use App\Ticketing\Jobs\ClassifyTicketJob;
use App\Ticketing\Support\HtmlSanitizer;
use App\Ticketing\Support\TicketNotifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Turns one inbound email into a ticket (always) or a message of an existing ticket.
 * The only reasons to skip are technical and always logged: duplicate, sent by us, bounce/auto-reply outside a thread.
 */
class ProcessInboundMessage
{
    public const DUPLICATE = 'duplicate';

    public const OWN = 'own_message';

    public const DISCARDED = 'discarded';

    public const ATTACHED = 'attached';

    public const REOPENED = 'reopened';

    public const CREATED = 'created';

    public function __construct(
        private readonly ThreadMatcher $matcher,
        private readonly TicketStateMachine $machine,
        private readonly TicketAssigner $assigner,
    ) {}

    public function handle(InboundEmail $email): string
    {
        if (SupportTicketMessage::query()->where('gmail_message_id', $email->gmailMessageId)->exists()) {
            return self::DUPLICATE;
        }

        if ($this->isOwnMessage($email)) {
            $this->log('skip', $email, 'inviato da noi');

            return self::OWN;
        }

        $automatic = $this->automaticReason($email);
        $match = $this->matcher->match($email);

        if ($automatic !== null && $match->ticket === null) {
            $this->log('scartata', $email, $automatic);
            ApplyMailboxLabelJob::dispatch($email->mailboxId, $email->gmailMessageId, config('ticketing.mailbox.labels.discarded'));

            return self::DISCARDED;
        }

        try {
            [$outcome, $ticket, $isNew] = DB::transaction(fn () => $this->persist($email, $match, $automatic));
        } catch (UniqueConstraintViolationException) {
            return self::DUPLICATE; // another worker stored it first
        }

        ApplyMailboxLabelJob::dispatch($email->mailboxId, $email->gmailMessageId, config('ticketing.mailbox.labels.imported'));

        if ($isNew) {
            $this->machine->opened($ticket);
            ClassifyTicketJob::dispatch($ticket->id, $email);
        }

        return $outcome;
    }

    /**
     * @return array{0: string, 1: SupportTicket, 2: bool}
     */
    private function persist(InboundEmail $email, MatchResult $match, ?string $automatic): array
    {
        if ($match->ticket !== null) {
            $ticket = $match->ticket;

            if ($automatic !== null) {
                // out-of-office inside an existing thread: kept for the record, state untouched
                $this->storeMessage($ticket, $email, MessageType::System);

                return [self::ATTACHED, $ticket, false];
            }

            if ($ticket->isClosed() && $this->closedTooLongAgo($ticket)) {
                return [self::CREATED, $this->createTicket($email, previous: $ticket), true];
            }

            $message = $this->storeMessage($ticket, $email, MessageType::Reply);

            if ($ticket->isClosed()) {
                $this->machine->reopen($ticket, $email->receivedAt);
                $this->ensureAssigneeIsUsable($ticket);
                TicketNotifier::user($ticket->loadMissing('assignee')->assignee, 'reopened', "Ticket {$ticket->public_id} riaperto dal cliente", $ticket);
                CustomerMessageReceived::dispatch($ticket, $message);

                return [self::REOPENED, $ticket, false];
            }

            $this->machine->customerMessage($ticket, $email->receivedAt);
            TicketNotifier::user($ticket->loadMissing('assignee')->assignee, 'reply', "Nuova risposta del cliente nel ticket {$ticket->public_id}", $ticket);
            CustomerMessageReceived::dispatch($ticket, $message);

            return [self::ATTACHED, $ticket, false];
        }

        return [self::CREATED, $this->createTicket($email, related: $match->related), true];
    }

    private function createTicket(InboundEmail $email, ?SupportTicket $previous = null, ?SupportTicket $related = null): SupportTicket
    {
        $ticket = SupportTicket::openNew([
            'status' => TicketStatus::Open,
            // replies leave from the mailbox the customer wrote to
            'mailbox_id' => $email->mailboxId ?? $previous?->mailbox_id,
            'client_id' => self::clientIdFor($email->fromEmail),
            'customer_email' => $email->fromEmail,
            'customer_name' => $email->fromName,
            'subject' => $email->subject,
            'gmail_thread_id' => $email->gmailThreadId,
            'last_inbound_at' => $email->receivedAt,
            'previous_ticket_id' => $previous?->id,
            'related_ticket_id' => $related?->id,
            // a ticket reopened as a new one inherits the old type as a hint; classification confirms or changes it
            'support_ticket_type_id' => $previous?->support_ticket_type_id,
        ]);

        $this->storeMessage($ticket, $email, MessageType::Reply);

        return $ticket;
    }

    private function storeMessage(SupportTicket $ticket, InboundEmail $email, MessageType $type): SupportTicketMessage
    {
        $message = SupportTicketMessage::create([
            'support_ticket_id' => $ticket->id,
            'direction' => MessageDirection::In,
            'type' => $type,
            'delivery_status' => DeliveryStatus::Received,
            'gmail_message_id' => $email->gmailMessageId,
            'message_id_header' => $email->messageIdHeader,
            'in_reply_to' => $email->inReplyTo,
            'references' => $email->references === [] ? null : implode(' ', $email->references),
            'from_email' => $email->fromEmail,
            'from_name' => $email->fromName,
            'to_email' => $email->to[0] ?? null,
            'subject' => $email->subject,
            'body_text' => $email->textBody,
            'body_html_sanitized' => HtmlSanitizer::clean($email->htmlBody),
            'received_at' => $email->receivedAt,
        ]);

        foreach ($email->attachments as $attachment) {
            SupportTicketAttachment::create([
                'support_ticket_message_id' => $message->id,
                'filename' => $attachment->filename,
                'mime' => $attachment->mime,
                'size' => $attachment->size,
                'content_id' => $attachment->contentId,
                'inline' => $attachment->inline,
                'disk' => $attachment->storedPath ? config('ticketing.mailbox.storage_disk') : null,
                'path' => $attachment->storedPath,
                'skipped_too_large' => $attachment->tooLarge,
            ]);
        }

        return $message;
    }

    private function ensureAssigneeIsUsable(SupportTicket $ticket): void
    {
        $assignee = $ticket->loadMissing('assignee')->assignee;

        if ($assignee === null) {
            $this->assigner->assignAutomatically($ticket);

            return;
        }

        if (! $assignee->isTicketAgent() || ! $assignee->ticket_available) {
            $this->assigner->reassignAutomatically($ticket, AssignmentReason::Auto);
        }

        $ticket->unsetRelation('assignee');
    }

    private function closedTooLongAgo(SupportTicket $ticket): bool
    {
        return $ticket->closed_at !== null
            && $ticket->closed_at->lt(now()->subDays((int) config('ticketing.reopen_days')));
    }

    /**
     * The agency client with this email address, if any (case-insensitive).
     */
    public static function clientIdFor(string $email): ?int
    {
        return Client::query()->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->value('id');
    }

    private function isOwnMessage(InboundEmail $email): bool
    {
        $ownAddresses = Mailbox::query()->pluck('address')->map(fn (string $address) => mb_strtolower($address));

        return $ownAddresses->contains($email->fromEmail)
            || in_array('\\Sent', $email->labels, true);
    }

    /**
     * Bounces and auto-replies (not newsletters: those become tickets of type "notifica automatica").
     */
    private function automaticReason(InboundEmail $email): ?string
    {
        $local = strstr($email->fromEmail, '@', true) ?: $email->fromEmail;

        if (in_array($local, ['mailer-daemon', 'postmaster'], true)) {
            return 'bounce';
        }

        $returnPath = $email->header('return-path');
        if ($returnPath !== null && in_array(trim($returnPath, '<> '), [''], true)) {
            return 'bounce';
        }

        $autoSubmitted = $email->header('auto-submitted');
        if ($autoSubmitted !== null && strtolower(trim($autoSubmitted)) !== 'no') {
            return 'auto_reply';
        }

        if ($email->header('x-autoreply') !== null || $email->header('x-autorespond') !== null) {
            return 'auto_reply';
        }

        return null;
    }

    private function log(string $action, InboundEmail $email, string $reason): void
    {
        Log::info("Ticketing: email {$action}", ['gmail_message_id' => $email->gmailMessageId, 'reason' => $reason]);
    }
}
