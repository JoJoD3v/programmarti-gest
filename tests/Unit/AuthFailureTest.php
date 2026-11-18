<?php

namespace Tests\Unit;

use App\Ticketing\Mailbox\AuthFailure;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;

class AuthFailureTest extends TestCase
{
    public function test_imap_authenticationfailed_is_definitive(): void
    {
        $this->assertTrue(AuthFailure::imap('[AUTHENTICATIONFAILED] Invalid credentials (Failure)'));
    }

    public function test_imap_temporary_refusals_are_not(): void
    {
        $this->assertFalse(AuthFailure::imap('[ALERT] Too many simultaneous connections. (Failure)'));
        $this->assertFalse(AuthFailure::imap('[UNAVAILABLE] Temporary System Problem. Try again later'));
        $this->assertFalse(AuthFailure::imap('[ALERT] Please log in via your web browser'));
    }

    public function test_imap_xoauth2_challenge_401_is_definitive(): void
    {
        $this->assertTrue(AuthFailure::imap('Invalid credentials', '{"status":"401","schemes":"Bearer","scope":"https://mail.google.com/"}'));
        $this->assertFalse(AuthFailure::imap('x', '{"status":"400"}'));
    }

    public function test_smtp_rejected_password_is_definitive(): void
    {
        $this->assertTrue(AuthFailure::smtp(self::authFailure('LOGIN', 535, '535-5.7.8 Username and Password not accepted.')));
    }

    public function test_smtp_xoauth2_error_challenge_is_definitive(): void
    {
        $this->assertTrue(AuthFailure::smtp(self::authFailure('XOAUTH2', 334, '334 eyJzdGF0dXMiOiI0MDAiLCJzY2hlbWVzIjoiQmVhcmVyIn0=')));
    }

    public function test_smtp_temporary_auth_refusals_are_not(): void
    {
        $this->assertFalse(AuthFailure::smtp(self::authFailure('LOGIN', 454, '454 4.7.0 Too many login attempts, please try again later.')));
    }

    public function test_smtp_failures_outside_the_auth_step_are_not(): void
    {
        $this->assertFalse(AuthFailure::smtp(new TransportException('Expected response code "250/251/252" but got code "535", with message "535 5.7.1 rcpt rejected".', 535)));
        $this->assertFalse(AuthFailure::smtp(new TransportException('Expected response code "250" but got code "550", with message "550 5.1.1 <utente535@example.com> does not exist".', 550)));
        $this->assertFalse(AuthFailure::smtp(new \RuntimeException('Failed to authenticate on SMTP server', 535)));
    }

    /**
     * Shaped exactly as EsmtpTransport::handleAuth() builds it.
     */
    public static function authFailure(string $authenticator, int $code, string $serverMessage): TransportException
    {
        return new TransportException(
            sprintf('Failed to authenticate on SMTP server with username "negozio@gmail.com" using the following authenticators: "%s".', $authenticator)
            .sprintf(' Authenticator "%s" returned "Expected response code "235" but got code "%d", with message "%s".".', $authenticator, $code, $serverMessage),
            $code,
        );
    }
}
