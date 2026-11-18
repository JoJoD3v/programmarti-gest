<?php

namespace App\Ticketing\Mailbox\Imap;

use App\Ticketing\Mailbox\AuthFailure;

/**
 * Minimal IMAP client over a TLS socket: just what the sync needs, with raw access to Gmail extensions.
 * Reading always uses BODY.PEEK so nothing is ever marked as read.
 */
class ImapConnection
{
    /** @var resource|null */
    private $socket = null;

    private int $counter = 0;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly int $timeout = 20,
    ) {}

    public function connect(): void
    {
        $socket = @stream_socket_client("ssl://{$this->host}:{$this->port}", $errno, $error, $this->timeout);

        if ($socket === false) {
            throw new ImapException("Connessione IMAP fallita ({$errno}): {$error}");
        }

        stream_set_timeout($socket, $this->timeout);
        $this->socket = $socket;

        $greeting = $this->readLine();
        if (! str_starts_with($greeting, '* OK') && ! str_starts_with($greeting, '* PREAUTH')) {
            throw new ImapException('Risposta IMAP inattesa: '.trim($greeting));
        }
    }

    public function login(string $username, string $password): void
    {
        $response = $this->command('LOGIN '.$this->quote($username).' '.$this->quote($password));

        if (! $response->ok()) {
            $message = 'Login IMAP rifiutato: '.$response->text;

            throw AuthFailure::imap($response->text)
                ? ImapException::authentication($message)
                : new ImapException($message);
        }
    }

    /**
     * SASL XOAUTH2 with the initial response inline. On failure Gmail sends a "+ <base64 json>" challenge
     * that must be answered with an empty line before the tagged NO arrives.
     */
    public function authenticateXOAuth2(string $user, string $accessToken): void
    {
        $tag = $this->nextTag();
        $this->write("{$tag} AUTHENTICATE XOAUTH2 ".self::xoauth2($user, $accessToken)."\r\n");

        $challenge = null;

        while (true) {
            $line = $this->readLine();

            if (str_starts_with($line, '+')) {
                $decoded = base64_decode(trim(substr($line, 1)), true);
                $challenge = $decoded === false ? null : $decoded;
                $this->write("\r\n");

                continue;
            }

            if (str_starts_with($line, "{$tag} ")) {
                $response = $this->tagged($tag, $line, []);

                break;
            }
        }

        if (! $response->ok()) {
            $message = 'Autenticazione XOAUTH2 rifiutata: '.$response->text;

            throw AuthFailure::imap($response->text, $challenge)
                ? ImapException::authentication($message)
                : new ImapException($message);
        }
    }

    public static function xoauth2(string $user, string $accessToken): string
    {
        return base64_encode("user={$user}\x01auth=Bearer {$accessToken}\x01\x01");
    }

    public function logout(): void
    {
        if ($this->socket === null) {
            return;
        }

        try {
            $this->command('LOGOUT');
        } catch (\Throwable) {
            // closing anyway
        }

        @fclose($this->socket);
        $this->socket = null;
    }

    /**
     * Sends one command and reads until its tagged completion.
     */
    public function command(string $command): ImapResponse
    {
        $tag = $this->nextTag();
        $this->write("{$tag} {$command}\r\n");

        $untagged = [];

        while (true) {
            $line = $this->readLine();

            if (str_starts_with($line, "{$tag} ")) {
                return $this->tagged($tag, $line, $untagged);
            }

            if (str_starts_with($line, '*')) {
                $untagged[] = $this->completeLiterals($line);
            }
        }
    }

    public function quote(string $value): string
    {
        return '"'.addcslashes($value, '"\\').'"';
    }

    private function nextTag(): string
    {
        return sprintf('A%04d', ++$this->counter);
    }

    /**
     * @param  list<string>  $untagged
     */
    private function tagged(string $tag, string $line, array $untagged): ImapResponse
    {
        [$status, $text] = array_pad(explode(' ', trim(substr($line, strlen($tag) + 1)), 2), 2, '');

        return new ImapResponse(strtoupper($status), $text, $untagged);
    }

    /**
     * A line ending in {N} announces N literal bytes, then the rest of the same response line follows.
     */
    private function completeLiterals(string $line): string
    {
        $buffer = rtrim($line, "\r\n");

        while (preg_match('/\{(\d+)\}$/', $buffer, $match) === 1) {
            $length = (int) $match[1];
            $buffer .= "\r\n".$this->readBytes($length);
            $buffer .= rtrim($this->readLine(), "\r\n");
        }

        return $buffer;
    }

    protected function write(string $data): void
    {
        if ($this->socket === null || @fwrite($this->socket, $data) === false) {
            throw new ImapException('Connessione IMAP chiusa.');
        }
    }

    protected function readLine(): string
    {
        $line = $this->socket !== null ? fgets($this->socket) : false;

        if ($line === false) {
            $meta = $this->socket !== null ? stream_get_meta_data($this->socket) : [];

            throw new ImapException(($meta['timed_out'] ?? false) ? 'Timeout lettura IMAP.' : 'Connessione IMAP interrotta.');
        }

        return $line;
    }

    private function readBytes(int $length): string
    {
        $data = '';

        while (strlen($data) < $length) {
            $chunk = fread($this->socket, $length - strlen($data));

            if ($chunk === false || $chunk === '') {
                throw new ImapException('Connessione IMAP interrotta durante il download di un messaggio.');
            }

            $data .= $chunk;
        }

        return $data;
    }
}
