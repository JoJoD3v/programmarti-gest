<?php

namespace App\Ticketing\Actions;

use App\Models\SupportTicketMessage;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\DTO\OutboundEmail;
use App\Ticketing\Enums\DeliveryStatus;
use App\Ticketing\Support\SendLimiter;
use App\Ticketing\Support\TicketingSettings;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Sends one stored outbound message through the mailbox. Idempotent: a message already sent is never sent twice.
 */
class DeliverMessage
{
    public function __construct(
        private readonly MailboxClient $client,
        private readonly SendLimiter $limiter,
        private readonly TicketingSettings $settings,
    ) {}

    /**
     * @throws SendLimitReached
     */
    public function handle(SupportTicketMessage $message): void
    {
        if ($message->delivery_status === DeliveryStatus::Sent) {
            return;
        }

        $ticket = $message->ticket;
        $mailbox = $ticket->sendingMailbox() ?? throw new RuntimeException('Nessuna casella collegata da cui inviare.');

        if (! $this->limiter->reserve($mailbox)) {
            throw new SendLimitReached;
        }

        $html = $message->body_html_sanitized ?? $this->composeHtml($message);

        $attachments = $message->attachments->map(fn ($attachment) => [
            'path' => Storage::disk($attachment->disk)->path($attachment->path),
            'name' => $attachment->filename,
            'mime' => $attachment->mime ?: 'application/octet-stream',
        ])->all();

        $this->client->send($mailbox, new OutboundEmail(
            to: $ticket->customer_email, // never taken from a request
            subject: (string) $message->subject,
            textBody: $this->composeText($message),
            htmlBody: $html,
            messageId: (string) $message->message_id_header,
            inReplyTo: $message->in_reply_to,
            references: preg_split('/\s+/', trim((string) $message->references), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            attachments: $attachments,
        ));

        $message->forceFill([
            'delivery_status' => DeliveryStatus::Sent,
            'sent_at' => now(),
            'send_error' => null,
            'body_html_sanitized' => $html,
        ])->save();
    }

    private function signature(SupportTicketMessage $message): string
    {
        $signature = trim((string) $this->settings->get('reply_signature'));

        return str_replace('{dipendente_nome}', (string) $message->sender?->first_name, $signature);
    }

    private function composeText(SupportTicketMessage $message): string
    {
        $text = (string) $message->body_text;
        $signature = $this->signature($message);

        return $signature !== '' ? "{$text}\n\n-- \n{$signature}" : $text;
    }

    private function composeHtml(SupportTicketMessage $message): string
    {
        $html = nl2br(e((string) $message->body_text));
        $signature = $this->signature($message);

        if ($signature !== '') {
            $html .= '<br><br><span style="color:#64748b">'.nl2br(e($signature)).'</span>';
        }

        return '<div style="font-family:Arial,sans-serif;font-size:14px;line-height:1.5">'.$html.'</div>';
    }
}
