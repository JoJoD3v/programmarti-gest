<?php

namespace App\Http\Controllers;

use App\Models\Mailbox;
use App\Models\SupportTicket;
use App\Ticketing\Contracts\MailboxClient;
use App\Ticketing\Mailbox\GoogleOAuth;
use App\Ticketing\Mailbox\MailboxAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Connected mailboxes (Gmail or any IMAP/SMTP server). Credentials are saved only if the connection test
 * (IMAP + SMTP login) passes.
 */
class SupportMailboxController extends Controller
{
    /**
     * Server defaults per driver, used when the host fields are left empty.
     */
    private const DEFAULTS = [
        Mailbox::DRIVER_GMAIL => ['imap_host' => 'imap.gmail.com', 'imap_port' => 993, 'smtp_host' => 'smtp.gmail.com', 'smtp_port' => 465],
        Mailbox::DRIVER_IMAP => ['imap_port' => 993, 'smtp_port' => 465],
    ];

    public function index(): View
    {
        Gate::authorize('manage', SupportTicket::class);

        return view('support.mailboxes.index', [
            'mailboxes' => Mailbox::query()->withCount('tickets')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('manage', SupportTicket::class);

        return view('support.mailboxes.form', [
            'mailbox' => new Mailbox(['driver' => Mailbox::DRIVER_IMAP, 'folder' => 'INBOX', 'active' => true] + self::DEFAULTS[Mailbox::DRIVER_IMAP]),
            'oauthConfigured' => false,
        ]);
    }

    public function store(Request $request, MailboxClient $client): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $mailbox = new Mailbox;
        $error = $this->fillAndTest($request, $client, $mailbox);

        if ($error !== null) {
            return back()->withInput($request->except('password'))->with('error', $error);
        }

        $mailbox->save();

        return redirect()->route('support.mailboxes.edit', $mailbox)->with('success', 'Casella collegata: IMAP e SMTP funzionano.');
    }

    public function edit(Mailbox $mailbox): View
    {
        Gate::authorize('manage', SupportTicket::class);

        return view('support.mailboxes.form', [
            'mailbox' => $mailbox,
            'oauthConfigured' => $mailbox->isGmail() && app(GoogleOAuth::class)->configured(),
        ]);
    }

    public function update(Request $request, MailboxClient $client, Mailbox $mailbox): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $error = $this->fillAndTest($request, $client, $mailbox);

        if ($error !== null) {
            return back()->withInput($request->except('password'))->with('error', $error);
        }

        $mailbox->save();
        $mailbox->reconnect();

        return back()->with('success', 'Casella aggiornata: IMAP e SMTP funzionano.');
    }

    /**
     * A mailbox with tickets is only deactivated: its tickets keep the link and replies move to another mailbox.
     */
    public function destroy(Mailbox $mailbox): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        if ($mailbox->tickets()->exists()) {
            $mailbox->forceFill(['active' => false])->save();

            return redirect()->route('support.mailboxes.index')->with('success', "Casella {$mailbox->address} disattivata: ha dei ticket, quindi non viene eliminata.");
        }

        $mailbox->delete();

        return redirect()->route('support.mailboxes.index')->with('success', "Casella {$mailbox->address} eliminata.");
    }

    public function reactivate(MailboxClient $client, Mailbox $mailbox): RedirectResponse
    {
        Gate::authorize('manage', SupportTicket::class);

        $status = $client->testConnection($mailbox);

        if (! $status->ok()) {
            return back()->with('error', 'La connessione non funziona ancora: '.($status->error ?? 'verifica la password').'.');
        }

        $mailbox->forceFill(['last_verified_at' => now()])->save();
        $mailbox->reconnect();

        return back()->with('success', 'Sincronizzazione riattivata.');
    }

    /**
     * Validates the form into the (unsaved) model and runs the connection test with those exact credentials.
     * Returns the error to show, or null when the mailbox can be saved.
     */
    private function fillAndTest(Request $request, MailboxClient $client, Mailbox $mailbox): ?string
    {
        $driver = (string) $request->input('driver', $mailbox->driver);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'email', 'max:255', Rule::unique('mailboxes', 'address')->ignore($mailbox->id)],
            'driver' => ['required', Rule::in([Mailbox::DRIVER_GMAIL, Mailbox::DRIVER_IMAP])],
            'imap_host' => [$driver === Mailbox::DRIVER_IMAP ? 'required' : 'nullable', 'string', 'max:255'],
            'imap_port' => ['nullable', 'integer', 'between:1,65535'],
            'smtp_host' => [$driver === Mailbox::DRIVER_IMAP ? 'required' : 'nullable', 'string', 'max:255'],
            'smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => [$mailbox->exists ? 'nullable' : 'required', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
        ]);

        $address = mb_strtolower($data['address']);
        $isGmail = $data['driver'] === Mailbox::DRIVER_GMAIL;
        $defaults = self::DEFAULTS[$data['driver']];

        $mailbox->fill([
            'name' => $data['name'],
            'address' => $address,
            'driver' => $data['driver'],
            // Gmail always talks to Google's servers: the stored password and token can never be pointed elsewhere
            'imap_host' => $isGmail ? $defaults['imap_host'] : $data['imap_host'],
            'imap_port' => $isGmail ? $defaults['imap_port'] : ($data['imap_port'] ?? $defaults['imap_port']),
            'smtp_host' => $isGmail ? $defaults['smtp_host'] : $data['smtp_host'],
            'smtp_port' => $isGmail ? $defaults['smtp_port'] : ($data['smtp_port'] ?? $defaults['smtp_port']),
            'username' => filled($data['username'] ?? null) ? $data['username'] : $address,
            'folder' => filled($data['folder'] ?? null) ? $data['folder'] : 'INBOX',
            'active' => $request->boolean('active'),
        ]);

        // the saved secrets belong to the old account and server: a new target needs them typed again
        $targetChanged = $mailbox->exists && $mailbox->isDirty(['address', 'driver', 'imap_host', 'imap_port', 'smtp_host', 'smtp_port', 'username']);

        if ($targetChanged && blank($data['password'] ?? null)) {
            throw ValidationException::withMessages(['password' => 'Hai cambiato indirizzo, server o utente: reinserisci la password.']);
        }

        if (filled($data['password'] ?? null)) {
            $mailbox->password = Mailbox::normalizePassword($data['password'], $data['driver']);
        }

        if (! $mailbox->isGmail() || $targetChanged) {
            $mailbox->forceFill(['oauth_refresh_token' => null, 'oauth_connected_at' => null]);
        }

        try {
            // the explicit account tests exactly these credentials (no OAuth, no fallback)
            $status = $client->testConnection($mailbox, new MailboxAccount($mailbox));
        } catch (\Throwable $exception) {
            return 'Test di connessione fallito: '.$exception->getMessage();
        }

        if (! $status->ok()) {
            return 'Test di connessione fallito: '.($status->error ?? 'credenziali non valide').' Nulla è stato salvato.';
        }

        $mailbox->last_verified_at = now();

        return null;
    }
}
