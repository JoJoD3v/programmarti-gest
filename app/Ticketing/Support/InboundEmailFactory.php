<?php

namespace App\Ticketing\Support;

use App\Models\SupportTicketMessage;
use App\Ticketing\DTO\InboundEmail;
use Carbon\CarbonImmutable;

class InboundEmailFactory
{
    /**
     * Rebuilds the DTO from a stored message (original headers are not kept, so mass-mail hints are lost).
     */
    public static function fromMessage(SupportTicketMessage $message): InboundEmail
    {
        return new InboundEmail(
            gmailMessageId: (string) $message->gmail_message_id,
            gmailThreadId: $message->ticket?->gmail_thread_id,
            uid: null,
            messageIdHeader: $message->message_id_header,
            inReplyTo: $message->in_reply_to,
            references: preg_split('/\s+/', trim((string) $message->references), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fromEmail: (string) $message->from_email,
            fromName: $message->from_name,
            to: array_filter([$message->to_email]),
            subject: (string) $message->subject,
            textBody: (string) $message->body_text,
            htmlBody: null,
            receivedAt: CarbonImmutable::instance($message->happenedAt()),
            mailboxId: $message->ticket?->mailbox_id,
        );
    }
}
