<?php

namespace App\Ticketing\DTO;

use Carbon\CarbonImmutable;

final readonly class InboundEmail
{
    /**
     * @param  list<string>  $references
     * @param  list<string>  $to
     * @param  list<string>  $labels
     * @param  array<string, string>  $headers
     * @param  list<InboundAttachment>  $attachments
     */
    public function __construct(
        public string $gmailMessageId,
        public ?string $gmailThreadId,
        public ?int $uid,
        public ?string $messageIdHeader,
        public ?string $inReplyTo,
        public array $references,
        public string $fromEmail,
        public ?string $fromName,
        public array $to,
        public string $subject,
        public string $textBody,
        public ?string $htmlBody,
        public CarbonImmutable $receivedAt,
        public array $labels = [],
        public array $headers = [],
        public array $attachments = [],
        public ?int $mailboxId = null,
    ) {}

    /**
     * @param  list<InboundAttachment>  $attachments
     */
    public function withAttachments(array $attachments): self
    {
        return new self(
            $this->gmailMessageId, $this->gmailThreadId, $this->uid, $this->messageIdHeader, $this->inReplyTo,
            $this->references, $this->fromEmail, $this->fromName, $this->to, $this->subject, $this->textBody,
            $this->htmlBody, $this->receivedAt, $this->labels, $this->headers, $attachments, $this->mailboxId,
        );
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
