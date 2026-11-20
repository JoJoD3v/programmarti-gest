<?php

namespace App\Ticketing\Support;

class TicketSubject
{
    /**
     * The original subject only: reply prefixes and the [T-1042] token are stripped.
     * Threading relies on the Gmail thread and the In-Reply-To/References headers.
     */
    public static function forReply(?string $original, string $publicId): string
    {
        $subject = trim((string) $original);
        $subject = preg_replace('/^(\s*(re|r|risp|rif|fwd?|i)\s*:\s*)+/i', '', $subject) ?? $subject;
        $subject = trim(str_replace("[{$publicId}]", '', $subject));
        $subject = preg_replace('/\s{2,}/', ' ', $subject) ?? $subject;

        return trim($subject);
    }
}
