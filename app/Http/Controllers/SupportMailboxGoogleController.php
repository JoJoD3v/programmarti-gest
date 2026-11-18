<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Models\SupportTicket;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\Mailbox\GoogleOAuth;
use App\Ticketing\Mailbox\GoogleOAuthException;
use App\Ticketing\Mailbox\MailboxAccount;
use App\Ticketing\Support\TicketNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Google OAuth link of a Gmail mailbox. The callback URL is a single fixed route (registered on Google Cloud):
 * the mailbox being linked travels in the session together with the anti-forgery state.
 */
class SupportMailboxGoogleController extends Controller
{
    private const STATE_KEY = 'ticketing.google_oauth_state';

    public function redirect(Request $request, GoogleOAuth $google, Mailbox $mailbox): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        if (! $google->configured()) {
            return back()->with('error', 'Google OAuth non è configurato: imposta GOOGLE_OAUTH_CLIENT_ID e GOOGLE_OAUTH_CLIENT_SECRET nel .env.');
        }

        if (! $mailbox->isGmail()) {
            return back()->with('error', 'Il collegamento Google vale solo per le caselle Gmail.');
        }

        $state = Str::random(40);
        $request->session()->put(self::STATE_KEY, ['state' => $state, 'mailbox' => $mailbox->id]);

        return redirect()->away($google->authorizationUrl($state, $mailbox->address));
    }

    /**
     * The link is saved only if the connection test with the fresh access token passes.
     */
    public function callback(Request $request, GoogleOAuth $google, MailboxClient $client): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $pending = (array) $request->session()->pull(self::STATE_KEY, []);
        $expectedState = (string) ($pending['state'] ?? '');
        $mailbox = isset($pending['mailbox']) ? Mailbox::query()->find($pending['mailbox']) : null;

        if ($expectedState === '' || ! hash_equals($expectedState, (string) $request->query('state'))) {
            return $this->failed($mailbox, 'Richiesta di collegamento non valida o scaduta: riprova da "Collega con Google".');
        }

        if ($request->query('error')) {
            return $this->failed($mailbox, $request->query('error') === 'access_denied'
                ? 'Collegamento annullato: nulla è cambiato.'
                : 'Google ha rifiutato il collegamento: nulla è cambiato.');
        }

        if ($mailbox === null || ! $mailbox->isGmail() || ! $google->configured()) {
            return $this->failed($mailbox, 'Collega prima la casella Gmail e configura Google OAuth.');
        }

        try {
            $tokens = $google->exchangeCode((string) $request->query('code'));
        } catch (GoogleOAuthException $exception) {
            return $this->failed($mailbox, 'Collegamento con Google fallito: '.$exception->getMessage());
        }

        if (mb_strtolower((string) $tokens['email']) !== mb_strtolower($mailbox->address)) {
            return $this->failed($mailbox, 'Hai autorizzato '.($tokens['email'] ?? 'un account sconosciuto').', ma la casella è '.$mailbox->address.'. Nulla è stato salvato.');
        }

        if (blank($tokens['refresh_token'])) {
            return $this->failed($mailbox, 'Google non ha restituito il token di aggiornamento: ripeti il collegamento.');
        }

        try {
            $status = $client->testConnection($mailbox, new MailboxAccount($mailbox, $tokens['access_token']));
        } catch (\Throwable $exception) {
            return $this->failed($mailbox, 'Test di connessione fallito: '.$exception->getMessage());
        }

        if (! $status->ok()) {
            return $this->failed($mailbox, 'Test di connessione fallito: '.($status->error ?? 'credenziali non valide').' Nulla è stato salvato.');
        }

        $mailbox->forceFill([
            'oauth_refresh_token' => $tokens['refresh_token'],
            'oauth_connected_at' => now(),
            'last_verified_at' => now(),
        ])->save();

        $mailbox->reconnect();
        TicketNotifier::clearAlert("mailbox-oauth-revoked-{$mailbox->id}");

        return redirect()->route('support.mailboxes.edit', $mailbox)->with('success', 'Collegamento Google attivo: IMAP e SMTP funzionano.');
    }

    public function destroy(Mailbox $mailbox): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $mailbox->forceFill(['oauth_refresh_token' => null, 'oauth_connected_at' => null])->save();

        return back()->with('success', 'Google scollegato: la casella usa la password per le app.');
    }

    private function failed(?Mailbox $mailbox, string $message): RedirectResponse
    {
        return redirect()->route($mailbox ? 'support.mailboxes.edit' : 'support.mailboxes.index', $mailbox ? [$mailbox] : [])->with('error', $message);
    }
}
