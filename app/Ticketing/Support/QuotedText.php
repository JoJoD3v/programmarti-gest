<?php

namespace App\Ticketing\Support;

use EmailReplyParser\EmailReplyParser;

/**
 * Separates the new part of an email from the quoted history.
 */
class QuotedText
{
    public static function visible(string $text): string
    {
        try {
            $visible = trim(EmailReplyParser::parseReply($text));
        } catch (\Throwable) {
            return trim($text);
        }

        return $visible !== '' ? $visible : trim($text);
    }

    /**
     * The part removed by visible(), for the expandable "show quoted text".
     */
    public static function quoted(string $text): string
    {
        $visible = self::visible($text);
        $position = strpos($text, $visible);

        if ($position === false) {
            return '';
        }

        return trim(substr($text, $position + strlen($visible)));
    }
}
