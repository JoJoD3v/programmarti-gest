<?php

namespace App\Ticketing\Support;

use App\Ticketing\DTO\InboundEmail;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Attachment bytes are written to the private disk before the message is queued: queue payloads stay small
 * and nothing binary travels through the jobs table.
 */
class InboundEmailStore
{
    public static function disk(): Filesystem
    {
        return Storage::disk(config('ticketing.mailbox.storage_disk'));
    }

    public function stash(InboundEmail $email): InboundEmail
    {
        $attachments = [];

        foreach ($email->attachments as $index => $attachment) {
            if ($attachment->content === null) {
                $attachments[] = $attachment;

                continue;
            }

            $path = sprintf(
                '%s/attachments/%s/%d-%s',
                config('ticketing.mailbox.storage_path'),
                $email->gmailMessageId,
                $index,
                Str::slug(pathinfo($attachment->filename, PATHINFO_FILENAME)).'.'.(pathinfo($attachment->filename, PATHINFO_EXTENSION) ?: 'bin'),
            );

            self::disk()->put($path, $attachment->content);
            $attachments[] = $attachment->withStoredPath($path);
        }

        return $email->withAttachments($attachments);
    }
}
