<?php

namespace App\Ticketing\Mailbox\Imap;

use Carbon\CarbonImmutable;

/**
 * Parsing of the untagged responses the sync depends on (FETCH with Gmail extensions, SEARCH, LIST, EXAMINE).
 * Kept free of any socket so it can be tested with recorded server output.
 */
class GmailImapExtensions
{
    /**
     * @param  list<string>  $untagged
     * @return list<array{uid: int, msgid: ?string, thrid: ?string, labels: list<string>, flags: list<string>, internal_date: ?CarbonImmutable, body: ?string}>
     */
    public static function parseFetchResponses(array $untagged): array
    {
        $messages = [];

        foreach ($untagged as $line) {
            if (preg_match('/^\* \d+ FETCH \(/', $line, $head) !== 1) {
                continue;
            }

            $offset = strlen($head[0]);
            $items = self::parseItems($line, $offset);

            if (! isset($items['UID'])) {
                continue;
            }

            $messages[] = [
                'uid' => (int) $items['UID'],
                'msgid' => isset($items['X-GM-MSGID']) ? (string) $items['X-GM-MSGID'] : null,
                'thrid' => isset($items['X-GM-THRID']) ? (string) $items['X-GM-THRID'] : null,
                'labels' => array_map('strval', (array) ($items['X-GM-LABELS'] ?? [])),
                'flags' => array_map('strval', (array) ($items['FLAGS'] ?? [])),
                'internal_date' => isset($items['INTERNALDATE']) ? self::parseInternalDate((string) $items['INTERNALDATE']) : null,
                'body' => self::bodyOf($items),
            ];
        }

        return $messages;
    }

    /**
     * @param  list<string>  $untagged
     * @return list<int>
     */
    public static function parseSearch(array $untagged): array
    {
        $uids = [];

        foreach ($untagged as $line) {
            if (preg_match('/^\* SEARCH ?(.*)$/', $line, $match) === 1 && trim($match[1]) !== '') {
                foreach (preg_split('/\s+/', trim($match[1])) as $uid) {
                    $uids[] = (int) $uid;
                }
            }
        }

        sort($uids);

        return array_values(array_unique($uids));
    }

    /**
     * @param  list<string>  $untagged
     * @return array{uidvalidity: ?string, uidnext: ?int}
     */
    public static function parseExamine(array $untagged): array
    {
        $validity = $next = null;

        foreach ($untagged as $line) {
            if (preg_match('/\[UIDVALIDITY (\d+)\]/', $line, $match) === 1) {
                $validity = $match[1];
            }
            if (preg_match('/\[UIDNEXT (\d+)\]/', $line, $match) === 1) {
                $next = (int) $match[1];
            }
        }

        return ['uidvalidity' => $validity, 'uidnext' => $next];
    }

    /**
     * Finds the folder carrying a SPECIAL-USE attribute (\All, \Sent, \Junk, \Trash): names are localized.
     *
     * @param  list<string>  $untagged
     * @return array<string, string> attribute (without backslash, lowercase) => folder name
     */
    public static function parseSpecialUse(array $untagged): array
    {
        $folders = [];

        foreach ($untagged as $line) {
            if (preg_match('/^\* LIST \(([^)]*)\) "?[^" ]*"? (?:"((?:[^"\\\\]|\\\\.)*)"|(\S+))$/', $line, $match) !== 1) {
                continue;
            }

            $name = ($match[2] ?? '') !== '' ? stripcslashes($match[2]) : ($match[3] ?? '');

            foreach (preg_split('/\s+/', trim($match[1])) as $attribute) {
                if (in_array(strtolower($attribute), ['\\all', '\\sent', '\\junk', '\\trash', '\\drafts'], true)) {
                    $folders[strtolower(ltrim($attribute, '\\'))] = $name;
                }
            }
        }

        return $folders;
    }

    public static function parseInternalDate(string $value): ?CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('d-M-Y H:i:s O', trim($value));
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $items
     */
    private static function bodyOf(array $items): ?string
    {
        foreach ($items as $key => $value) {
            if (str_starts_with($key, 'BODY[') && is_string($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Parses "KEY value KEY value …)" (a FETCH item list) starting at $offset; stops at the closing parenthesis.
     *
     * @return array<string, mixed>
     */
    private static function parseItems(string $input, int $offset): array
    {
        $items = [];
        $length = strlen($input);

        while ($offset < $length && $input[$offset] !== ')') {
            if ($input[$offset] === ' ') {
                $offset++;

                continue;
            }

            $key = self::readAtom($input, $offset);
            $offset++; // space after the key
            $items[strtoupper($key)] = self::readValue($input, $offset);
        }

        return $items;
    }

    private static function readAtom(string $input, int &$offset): string
    {
        $start = $offset;
        $length = strlen($input);

        // names such as BODY[] or BODY[HEADER]<0> may contain brackets
        while ($offset < $length && ! in_array($input[$offset], [' ', ')'], true)) {
            $offset++;
        }

        return substr($input, $start, $offset - $start);
    }

    private static function readValue(string $input, int &$offset): mixed
    {
        $length = strlen($input);

        if ($offset >= $length) {
            return null;
        }

        $char = $input[$offset];

        if ($char === '(') {
            $offset++;
            $list = [];

            while ($offset < $length && $input[$offset] !== ')') {
                if ($input[$offset] === ' ') {
                    $offset++;

                    continue;
                }
                $list[] = self::readValue($input, $offset);
            }
            $offset++; // closing parenthesis

            return $list;
        }

        if ($char === '"') {
            $offset++;
            $value = '';

            while ($offset < $length && $input[$offset] !== '"') {
                if ($input[$offset] === '\\' && $offset + 1 < $length) {
                    $offset++;
                }
                $value .= $input[$offset++];
            }
            $offset++; // closing quote

            return $value;
        }

        if ($char === '{' && preg_match('/\G\{(\d+)\}\r\n/', $input, $match, 0, $offset) === 1) {
            $offset += strlen($match[0]);
            $value = substr($input, $offset, (int) $match[1]);
            $offset += (int) $match[1];

            return $value;
        }

        return self::readAtom($input, $offset);
    }
}
