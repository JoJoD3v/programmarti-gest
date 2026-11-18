<?php

namespace App\Ticketing\Support;

class TicketSubject
{
    /**
     * "Re: {original} [T-1042]" without stacking "Re:" or repeating the token.
     */
    public static function forReply(?string $original, string $publicId): string
    {
        $subject = trim((string) $original);
        $subject = preg_replace('/^(\s*(re|r|risp|rif|fwd?|i)\s*:\s*)+/i', '', $subject) ?? $subject;
        $subject = trim(str_replace("[{$publicId}]", '', $subject));
        $subject = preg_replace('/\s{2,}/', ' ', $subject) ?? $subject;

        return trim("Re: {$subject} [{$publicId}]");
    }
}
