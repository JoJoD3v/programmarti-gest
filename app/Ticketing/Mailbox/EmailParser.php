<?php

namespace App\Ticketing\Mailbox;

use App\Ticketing\DTO\InboundAttachment;
use App\Ticketing\DTO\InboundEmail;
use Carbon\CarbonImmutable;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\IMessage;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * RFC822 source → InboundEmail (charsets to UTF-8, RFC 2047/2231 names, inline images told apart from attachments).
 */
class EmailParser
{
    /** Headers kept in InboundEmail::$headers (lowercase keys) for the technical skip rules. */
    private const KEPT_HEADERS = [
        'auto-submitted', 'precedence', 'x-autoreply', 'x-autorespond', 'list-id', 'list-unsubscribe',
        'return-path', 'x-failed-recipients', 'x-auto-response-suppress', 'content-type',
    ];

    /**
     * @param  array{gmail_message_id: string, gmail_thread_id?: ?string, uid?: ?int, labels?: list<string>, received_at?: ?CarbonImmutable, mailbox_id?: ?int}  $meta
     */
    public function parse(string $raw, array $meta, int $maxAttachmentBytes = 10485760): InboundEmail
    {
        $message = (new MailMimeParser)->parse($raw, false);

        $from = $message->getHeader('From');
        $fromEmail = $from instanceof AddressHeader ? (string) $from->getEmail() : '';
        $fromName = $from instanceof AddressHeader ? $from->getPersonName() : null;

        $html = $message->getHtmlContent();
        $text = $message->getTextContent();
        if ($text === null || trim($text) === '') {
            $text = $html !== null ? $this->htmlToText($html) : '';
        }

        $date = null;
        try {
            $header = $message->getHeaderValue('Date');
            $date = $header ? CarbonImmutable::parse($header) : null;
        } catch (\Throwable) {
            // unparsable Date header: fall back to the IMAP internal date
        }

        return new InboundEmail(
            gmailMessageId: $meta['gmail_message_id'],
            gmailThreadId: $meta['gmail_thread_id'] ?? null,
            uid: $meta['uid'] ?? null,
            messageIdHeader: $this->wrapId($message->getHeaderValue('Message-ID')),
            inReplyTo: $this->wrapId($message->getHeaderValue('In-Reply-To')),
            references: $this->ids((string) $message->getHeaderValue('References')),
            fromEmail: mb_strtolower(trim($fromEmail)),
            fromName: $fromName ? trim($fromName) : null,
            to: $this->recipients($message),
            subject: trim((string) $message->getSubject()),
            textBody: str_replace("\r\n", "\n", (string) $text),
            htmlBody: $html,
            receivedAt: $meta['received_at'] ?? $date ?? CarbonImmutable::now(),
            labels: $meta['labels'] ?? [],
            headers: $this->headers($message),
            attachments: $this->attachments($message, $maxAttachmentBytes),
            mailboxId: $meta['mailbox_id'] ?? null,
        );
    }

    /**
     * @return array<string, string>
     */
    private function headers(IMessage $message): array
    {
        $headers = [];
        foreach (self::KEPT_HEADERS as $name) {
            $value = $message->getHeaderValue($name);
            if ($value !== null) {
                $headers[$name] = $value;
            }
        }

        // Return-Path: <> is the bounce marker and parses to an empty value
        if ($message->getHeader('Return-Path') !== null && ! isset($headers['return-path'])) {
            $headers['return-path'] = '';
        }

        return $headers;
    }

    /**
     * @return list<string>
     */
    private function recipients(IMessage $message): array
    {
        $to = $message->getHeader('To');
        if (! $to instanceof AddressHeader) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($address) => mb_strtolower((string) $address->getEmail()),
            $to->getAddresses(),
        )));
    }

    /**
     * @return list<InboundAttachment>
     */
    private function attachments(IMessage $message, int $maxBytes): array
    {
        $attachments = [];

        foreach ($message->getAllAttachmentParts() as $index => $part) {
            $stream = $part->getContentStream();
            $size = $stream?->getSize() ?? 0;
            $contentId = $part->getContentId();
            $tooLarge = $size > $maxBytes;

            $attachments[] = new InboundAttachment(
                filename: $part->getFilename() ?: 'allegato-'.($index + 1),
                mime: $part->getContentType('application/octet-stream'),
                size: (int) $size,
                contentId: $contentId ? trim($contentId, '<>') : null,
                inline: $part->getContentDisposition() === 'inline' && $contentId !== null,
                tooLarge: $tooLarge,
                content: $tooLarge ? null : (string) $part->getContent(),
            );
        }

        return $attachments;
    }

    /**
     * @return list<string>
     */
    private function ids(string $value): array
    {
        preg_match_all('/<[^<>\s]+>/', $value, $matches);

        return $matches[0];
    }

    private function wrapId(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $ids = $this->ids($value);

        return $ids[0] ?? '<'.trim($value, '<> ').'>';
    }

    private function htmlToText(string $html): string
    {
        $html = preg_replace('#<(script|style)\b.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<br\s*/?>|</p>|</div>|</tr>|</li>#i', "\n", $html) ?? $html;

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
