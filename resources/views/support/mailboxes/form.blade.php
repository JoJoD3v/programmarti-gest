@extends('layouts.support')

@section('title', ($mailbox->exists ? 'Casella '.$mailbox->address : 'Collega casella').' — ProgrammArti')
@section('page-title', $mailbox->exists ? 'Casella '.$mailbox->address : 'Collega casella')

@section('content')
    <div class="max-w-2xl mx-auto space-y-6">
        <x-back-link :href="route('support.mailboxes.index')">Caselle email</x-back-link>

        @if ($mailbox->exists)
            <section class="card">
                <h2 class="card-title">Stato</h2>
                <dl class="p-5 text-sm grid grid-cols-2 gap-y-2">
                    <dt class="text-slate-600">Metodo di accesso</dt>
                    <dd>@if ($mailbox->oauth_refresh_token)Google OAuth (collegato il {{ $mailbox->oauth_connected_at?->format('d/m/Y H:i') ?? '—' }})@elseif ($mailbox->isGmail())Password per le app @else Utente e password @endif</dd>
                    <dt class="text-slate-600">Connessione</dt>
                    <dd>@if ($mailbox->isDisconnected())<span class="text-red-700 font-medium">Disconnessa dal {{ $mailbox->disconnected_at->format('d/m/Y H:i') }}</span>@elseif (! $mailbox->active)<span class="text-slate-700 font-medium">Disattiva</span>@else<span class="text-green-700 font-medium">Attiva</span>@endif</dd>
                    <dt class="text-slate-600">Ultima sincronizzazione</dt><dd>{{ $mailbox->last_synced_at?->diffForHumans() ?? 'mai' }}</dd>
                    <dt class="text-slate-600">Ultima verifica</dt><dd>{{ $mailbox->last_verified_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt class="text-slate-600">Errori consecutivi</dt><dd>{{ $mailbox->consecutive_failures }}</dd>
                    @if ($mailbox->last_error)<dt class="text-slate-600">Ultimo errore</dt><dd class="text-red-700 break-words">{{ $mailbox->last_error }}</dd>@endif
                </dl>
                @if ($mailbox->isDisconnected())
                    <form method="POST" action="{{ route('support.mailboxes.reactivate', $mailbox) }}" class="px-5 pb-5">
                        @csrf
                        <button class="btn btn-primary" type="submit">Riattiva sincronizzazione</button>
                        <p class="text-xs text-slate-600 mt-2">Se la password è stata cambiata, inserisci quella nuova qui sotto.</p>
                    </form>
                @endif
            </section>

            @if ($mailbox->isGmail())
                <section class="card">
                    <h2 class="card-title">Collegamento Google (OAuth)</h2>
                    <div class="p-5 text-sm space-y-3">
                        @if (! $oauthConfigured)
                            <p class="text-slate-600">Facoltativo. Per collegare Google imposta <code>GOOGLE_OAUTH_CLIENT_ID</code> e <code>GOOGLE_OAUTH_CLIENT_SECRET</code> nel file <code>.env</code> e lancia <code>php artisan optimize</code>. Senza OAuth la casella usa la password per le app.</p>
                        @else
                            <p class="text-slate-600">Con Google collegato la casella accede con OAuth; la password per le app resta salvata come riserva.</p>
                            <a class="btn btn-primary" href="{{ route('support.mailboxes.google.redirect', $mailbox) }}">{{ $mailbox->oauth_refresh_token ? 'Ricollega con Google' : 'Collega con Google' }}</a>
                        @endif
                        @if ($mailbox->oauth_refresh_token)
                            <form method="POST" action="{{ route('support.mailboxes.google.destroy', $mailbox) }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-secondary" type="submit">Scollega Google</button>
                            </form>
                        @endif
                    </div>
                </section>
            @endif
        @endif

        <x-form-card :action="$mailbox->exists ? route('support.mailboxes.update', $mailbox) : route('support.mailboxes.store')"
                     :method="$mailbox->exists ? 'PUT' : 'POST'" submit="Testa e salva" width="max-w-2xl">
            <p class="text-sm text-slate-600">Il salvataggio avviene solo se il test di connessione (IMAP e SMTP) riesce. La password non viene mai mostrata dopo il salvataggio.</p>

            <x-select name="driver" label="Tipo di casella" :required="true" :value="$mailbox->driver"
                      :options="['imap' => 'IMAP/SMTP (casella dell\'hosting, es. info@…)', 'gmail' => 'Gmail / Google Workspace']" />
            <x-field name="name" label="Nome mostrato al cliente come mittente" :required="true" :value="$mailbox->name ?: 'ProgrammArti'" />
            <x-field name="address" label="Indirizzo email" type="email" :required="true" :value="$mailbox->address" />
            <x-field name="username" label="Nome utente" :value="$mailbox->username" hint="Lascia vuoto se coincide con l'indirizzo email." />
            <x-field name="password" label="Password" type="password" :required="! $mailbox->exists" autocomplete="new-password"
                     hint="{{ $mailbox->exists ? 'Lascia vuoto per mantenere quella salvata.' : 'Per Gmail usa una password per le app (myaccount.google.com/apppasswords, serve la verifica in due passaggi).' }}" />

            <fieldset class="border border-slate-200 rounded-lg p-4 space-y-3">
                <legend class="px-2 text-sm font-medium text-slate-700">Server (solo IMAP/SMTP: per Gmail si usano sempre imap.gmail.com e smtp.gmail.com). Se cambi server va reinserita la password.</legend>
                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2"><x-field name="imap_host" label="Server IMAP" :value="$mailbox->imap_host" placeholder="mailserver15.vhosting-it.com" /></div>
                    <x-field name="imap_port" label="Porta IMAP (SSL)" type="number" :value="$mailbox->imap_port ?? 993" />
                    <div class="col-span-2"><x-field name="smtp_host" label="Server SMTP" :value="$mailbox->smtp_host" placeholder="mailserver15.vhosting-it.com" /></div>
                    <x-field name="smtp_port" label="Porta SMTP" type="number" :value="$mailbox->smtp_port ?? 465" />
                </div>
                <x-field name="folder" label="Cartella da leggere" :value="$mailbox->folder ?? 'INBOX'" />
            </fieldset>

            <input type="hidden" name="active" value="0">
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="active" value="1" class="rounded border-slate-300" @checked(old('active', $mailbox->active ?? true))>
                Attiva (sincronizza le email e può inviare risposte)
            </label>
        </x-form-card>

        @if ($mailbox->exists)
            <form method="POST" action="{{ route('support.mailboxes.destroy', $mailbox) }}" class="text-right"
                  onsubmit="return confirm('Eliminare la casella? Se ha dei ticket verrà solo disattivata.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger btn-sm">Elimina casella</button>
            </form>
        @endif
    </div>
@endsection
