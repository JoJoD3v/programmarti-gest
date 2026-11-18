<?php

namespace App\Ticketing\DTO;

final readonly class OutboundEmail
{
    /**
     * @param  list<string>  $references
     * @param  list<array{path: string, name: string, mime: string}>  $attachments
     */
    public function __construct(
        public string $to,
        public string $subject,
        public string $textBody,
        public ?string $htmlBody,
        public string $messageId,
        public ?string $inReplyTo = null,
        public array $references = [],
        public array $attachments = [],
    ) {}
}
