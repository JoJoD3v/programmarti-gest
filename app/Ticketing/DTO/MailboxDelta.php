<?php

namespace App\Ticketing\DTO;

final readonly class MailboxDelta
{
    /**
     * @param  list<InboundEmail>  $messages
     */
    public function __construct(
        public array $messages,
        public SyncCursor $cursor,
        public bool $hasMore = false,
    ) {}
}
