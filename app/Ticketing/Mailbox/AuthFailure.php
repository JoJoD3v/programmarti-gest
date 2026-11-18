<?php

namespace App\Ticketing\Mailbox;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

/**
 * Tells a definitive credential refusal apart from a temporary one (rate limits, outages, web login required).
 */
class AuthFailure
{
    /**
     * @param  string|null  $challenge  XOAUTH2 error JSON, already base64-decoded.
     */
    public static function imap(string $responseText, ?string $challenge = null): bool
    {
        if (stripos($responseText, '[AUTHENTICATIONFAILED]') !== false) {
            return true;
        }

        if ($challenge === null) {
            return false;
        }

        $payload = json_decode($challenge, true);

        return is_array($payload) && ($payload['status'] ?? null) === '401';
    }

    /**
     * Only the AUTH step counts (Symfony's EsmtpTransport::handleAuth() is the one place building this message):
     * 535 rejects a password, 334 is Gmail's XOAUTH2 error challenge for a rejected token. RCPT/DATA never match.
     */
    public static function smtp(Throwable $exception): bool
    {
        return $exception instanceof TransportExceptionInterface
            && str_starts_with($exception->getMessage(), 'Failed to authenticate on SMTP server')
            && in_array($exception->getCode(), [334, 535], true);
    }
}
