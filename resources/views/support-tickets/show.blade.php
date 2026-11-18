@extends('layouts.support')

@use('App\Ticketing\Support\HtmlSanitizer')
@use('App\Ticketing\Support\QuotedText')
@use('App\Ticketing\Enums\DeliveryStatus')
@use('App\Ticketing\Enums\MessageType')

@section('title', "{$ticket->public_id} — Ticket email")
@section('page-title', "Ticket {$ticket->public_id}")

@section('content')
    @php
        $isAdmin = auth()->user()->isTicketingAdmin();
    @endphp

    <div class="max-w-7xl mx-auto space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-3">
                <x-back-link :href="route('support-tickets.index')">Ticket email</x-back-link>
                <x-badge :status="$ticket->status" />
                @if ($ticket->type)
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->type->badgeClass() }}">{{ $ticket->type->name }}</span>
                @endif
                @if ($ticket->close_reason)
                    <span class="text-xs text-slate-600">{{ $ticket->close_reason->label() }}</span>
                @endif
                @if ($ticket->mailbox)
                    <span class="text-xs text-slate-600">via {{ $ticket->mailbox->address }}</span>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('support-tickets.print', $ticket) }}" target="_blank" class="btn btn-sm btn-secondary">Stampa</a>
            @can('close', $ticket)
                @unless ($ticket->isClosed())
                    <form method="POST" action="{{ route('support-tickets.close', $ticket) }}" class="flex items-center gap-2" onsubmit="return confirm('Chiudere il ticket?')">
                        @csrf
                        <select name="reason" class="filter-input" aria-label="Motivo di chiusura">
                            <option value="manual">Risolto</option>
                            <option value="not_relevant">Non pertinente</option>
                            <option value="spam">Spam</option>
                        </select>
                        <button class="btn btn-sm btn-secondary" type="submit">Chiudi ticket</button>
                    </form>
                @endunless
            @endcan
            </div>
        </div>

        @if ($ticket->related)
            <div class="px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800">
                Possibile correlato a <a class="underline font-medium" href="{{ route('support-tickets.show', $ticket->related) }}">{{ $ticket->related->public_id }}</a>
                (mittente diverso, per prudenza non sono stati uniti).
            </div>
        @endif
        @if ($ticket->previous)
            <div class="px-4 py-3 rounded-lg bg-slate-100 border border-slate-200 text-sm text-slate-700">
                Prosegue il ticket chiuso <a class="underline font-medium" href="{{ route('support-tickets.show', $ticket->previous) }}">{{ $ticket->previous->public_id }}</a>.
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            {{-- Conversazione + risposta --}}
            <div class="lg:col-span-2 space-y-5">
                <section class="card">
                    <h2 class="card-title">{{ $ticket->subject ?: '(senza oggetto)' }}</h2>
                    <div class="divide-y divide-slate-100">
                        @foreach ($messages as $message)
                            <article class="p-5 {{ $message->isInbound() ? '' : 'bg-indigo-50/40' }}" id="m{{ $message->id }}">
                                <header class="flex flex-wrap items-center justify-between gap-2 text-sm mb-3">
                                    <p class="font-medium">
                                        @if ($message->isInbound())
                                            {{ $message->from_name ?: $message->from_email }} <span class="text-xs text-slate-600">&lt;{{ $message->from_email }}&gt;</span>
                                        @else
                                            {{ $message->sender?->full_name ?? 'ProgrammArti' }} <span class="text-xs text-slate-600">→ cliente</span>
                                        @endif
                                        @if ($message->type === MessageType::System)<span class="text-xs text-slate-600">(risposta automatica)</span>@endif
                                    </p>
                                    <span class="text-xs text-slate-600">
                                        {{ $message->happenedAt()->format('d/m/Y H:i') }}
                                        @unless ($message->isInbound())· {{ $message->delivery_status->label() }}@endunless
                                    </span>
                                </header>

                                @if ($message->isInbound() && $message->body_html_sanitized)
                                    <iframe class="w-full border border-slate-100 rounded" sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"
                                            srcdoc="{{ HtmlSanitizer::forDisplay($message->body_html_sanitized, $showImages) }}"
                                            onload="this.style.height = (this.contentDocument.documentElement.scrollHeight + 8) + 'px'" title="Messaggio"></iframe>
                                    @if (! $showImages && str_contains($message->body_html_sanitized, '<img'))
                                        <a href="{{ request()->fullUrlWithQuery(['images' => 1]) }}#m{{ $message->id }}" class="text-xs link">Mostra immagini (possono tracciare l'apertura)</a>
                                    @endif
                                @else
                                    @php($visible = QuotedText::visible((string) $message->body_text))
                                    <div class="text-sm whitespace-pre-line break-words">{{ $visible }}</div>
                                    @if (($quoted = QuotedText::quoted((string) $message->body_text)) !== '')
                                        <details class="mt-2 text-xs text-slate-600"><summary class="cursor-pointer">Mostra testo citato</summary>
                                            <div class="mt-1 whitespace-pre-line break-words">{{ $quoted }}</div></details>
                                    @endif
                                @endif

                                @if ($message->attachments->isNotEmpty())
                                    <ul class="mt-3 flex flex-wrap gap-2 text-xs">
                                        @foreach ($message->attachments->where('inline', false) as $attachment)
                                            <li>
                                                @if ($attachment->path)
                                                    <a href="{{ route('support-attachments.download', $attachment) }}" class="inline-flex items-center gap-1 px-2 py-1 rounded bg-slate-100 hover:bg-slate-200">📎 {{ $attachment->filename }} <span class="text-slate-600">({{ number_format($attachment->size / 1024, 0) }} KB)</span></a>
                                                @else
                                                    <span class="inline-flex px-2 py-1 rounded bg-amber-50 text-amber-800" title="Troppo grande: apri l'email su Gmail">📎 {{ $attachment->filename }} (non scaricato, troppo grande)</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if ($message->delivery_status === DeliveryStatus::Failed)
                                    <div class="mt-3 p-3 rounded bg-red-50 border border-red-200 text-sm text-red-700 flex items-center justify-between gap-3">
                                        <span>Non inviato: {{ $message->send_error ?: 'errore sconosciuto' }}</span>
                                        <x-action-button :action="route('support-tickets.retry', [$ticket, $message->id])" variant="btn-secondary">Riprova</x-action-button>
                                    </div>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>

                {{-- Risposta --}}
                @can('reply', $ticket)
                    @unless ($ticket->isClosed())
                        <section class="card" id="reply">
                            <h2 class="card-title">Rispondi a {{ $ticket->customer_email }}</h2>
                            <form method="POST" action="{{ route('support-tickets.reply', $ticket) }}" enctype="multipart/form-data" class="p-5 space-y-3" id="reply-form">
                                @csrf
                                <input type="hidden" name="saved_reply_id" id="saved-reply-id" value="{{ old('saved_reply_id') }}">
                                <input type="hidden" name="ai_draft_id" id="ai-draft-id" value="{{ old('ai_draft_id') }}">

                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-secondary" id="open-saved" title="Ctrl+Maiusc+R">Risposte salvate</button>
                                    @if ($canDraft)
                                        <button type="button" class="btn btn-sm btn-secondary" id="gen-draft">✨ Genera bozza con AI</button>
                                        <input type="text" id="draft-instruction" maxlength="500" placeholder="Istruzioni per la bozza (facoltativo)" class="filter-input flex-1 min-w-48">
                                        <span class="flex gap-1">
                                            @foreach (['Più breve', 'Più formale', 'Più empatica'] as $quick)
                                                <button type="button" class="btn btn-sm btn-secondary quick-draft" data-text="{{ $quick }}">{{ $quick }}</button>
                                            @endforeach
                                        </span>
                                    @endif
                                </div>

                                @if ($suggestedDraft)
                                    <div class="p-3 rounded bg-indigo-50 border border-indigo-200 text-sm flex items-center justify-between gap-3">
                                        <span>È pronta una bozza suggerita dall'AI per l'ultimo messaggio.</span>
                                        <button type="button" class="btn btn-sm btn-primary" id="use-suggested" data-id="{{ $suggestedDraft->id }}" data-text="{{ $suggestedDraft->draft_text }}">Usa bozza suggerita</button>
                                    </div>
                                @endif
                                @if ($canDraft && ! $ticket->client)
                                    <p class="text-xs text-slate-600">Mittente non collegato a un cliente: la bozza non conoscerà i suoi progetti.</p>
                                @endif

                                <div id="draft-badge" class="hidden text-xs px-2 py-1 rounded bg-indigo-100 text-indigo-800 w-fit">Bozza AI — controlla prima di inviare
                                    <button type="button" class="ml-2 underline" data-feedback="up">👍</button>
                                    <button type="button" class="underline" data-feedback="down">👎</button>
                                </div>
                                <p id="draft-error" class="hidden text-sm text-red-600"></p>

                                <textarea name="body" id="reply-body" rows="10" required maxlength="20000"
                                          class="w-full px-4 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 {{ $errors->has('body') ? 'border-red-400 bg-red-50' : 'border-slate-300' }}"
                                          placeholder="Scrivi la risposta…">{{ old('body', $ticket->reply_draft) }}</textarea>
                                @error('body')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                                @error('attachments.*')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                                <p id="placeholder-warning" class="hidden text-sm text-amber-700">Ci sono ancora punti [[DA COMPLETARE…]]: l'invio è bloccato finché non li sostituisci.</p>

                                @if ($signature)
                                    <p class="text-xs text-slate-600">La firma verrà aggiunta in fondo: «{{ \Illuminate\Support\Str::limit(str_replace('{dipendente_nome}', auth()->user()->first_name, $signature), 80) }}»</p>
                                @endif

                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <input type="file" name="attachments[]" multiple class="text-sm" aria-label="Allegati">
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-secondary" id="save-as-reply">Salva come risposta</button>
                                        <button type="submit" name="close" value="1" class="btn btn-secondary">Rispondi e chiudi</button>
                                        <button type="submit" class="btn btn-primary" id="send-btn">Invia risposta</button>
                                    </div>
                                </div>
                            </form>
                        </section>
                    @else
                        <p class="text-sm text-slate-600">Ticket chiuso. Se il cliente scrive entro {{ config('ticketing.reopen_days') }} giorni si riapre da solo.</p>
                    @endunless
                @endcan
            </div>

            {{-- Colonna laterale --}}
            <aside class="space-y-5">
                <section class="card">
                    <h2 class="card-title">Cliente</h2>
                    <div class="p-5 text-sm space-y-1">
                        <p class="font-medium">{{ $ticket->customer_name ?: '—' }}</p>
                        <p><a class="link" href="mailto:{{ $ticket->customer_email }}">{{ $ticket->customer_email }}</a></p>
                        @if ($ticket->ai_summary)<p class="text-slate-600 pt-1"><span class="font-medium">Riassunto:</span> {{ $ticket->ai_summary }}</p>@endif
                        @if ($ticket->language && $ticket->language !== 'it')<p class="text-xs text-slate-600">Lingua rilevata: {{ $ticket->language }}</p>@endif
                    </div>
                </section>

                @can('changeType', $ticket)
                    <section class="card">
                        <h2 class="card-title">Tipologia</h2>
                        <form method="POST" action="{{ route('support-tickets.type', $ticket) }}" class="p-5 space-y-2">
                            @csrf @method('PATCH')
                            <select name="support_ticket_type_id" class="filter-input w-full" onchange="this.form.submit()">
                                @unless ($ticket->type)<option value="">Da classificare</option>@endunless
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected($ticket->support_ticket_type_id === $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                            @if ($ticket->type_source)
                                <p class="text-xs text-slate-600">Origine: {{ $ticket->type_source->label() }}@if ($ticket->ai_confidence !== null && $ticket->type_source->value === 'ai') ({{ round($ticket->ai_confidence * 100) }}%)@endif</p>
                            @endif
                        </form>
                        @if ($isAdmin && $ticket->type)
                            <form method="POST" action="{{ route('support-tickets.sender-rule', $ticket) }}" class="px-5 pb-5 text-xs space-y-1">
                                @csrf
                                <label class="flex items-center gap-1"><input type="checkbox" name="whole_domain" value="1"> tutto il dominio</label>
                                <button class="btn btn-sm btn-secondary" type="submit">Crea regola da questo mittente</button>
                            </form>
                        @endif
                    </section>
                @endcan

                <section class="card">
                    <h2 class="card-title">Cliente</h2>
                    <div class="p-5 text-sm space-y-2">
                        @if ($ticket->client)
                            <p>
                                @can('manage clients')
                                    <a href="{{ route('clients.show', $ticket->client) }}" class="link font-medium">{{ $ticket->client->full_name }}</a>
                                @else
                                    <span class="font-medium">{{ $ticket->client->full_name }}</span>
                                @endcan
                                <span class="text-xs text-slate-600">· {{ $ticket->client->email }}</span>
                            </p>
                            @if ($clientProjects->isNotEmpty())
                                <ul class="list-disc pl-4 text-xs space-y-0.5">
                                    @foreach ($clientProjects as $project)
                                        <li>{{ $project->name }} <span class="text-slate-600">· {{ \App\Models\Project::getStatuses()[$project->status] ?? $project->status }}</span></li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-xs text-slate-600">Nessun progetto.</p>
                            @endif
                        @else
                            <p class="text-xs text-slate-600">Il mittente non corrisponde a nessun cliente in anagrafica.</p>
                        @endif

                        @can('view', $ticket)
                            <form method="POST" action="{{ route('support-tickets.client', $ticket) }}" class="flex gap-2 pt-1">
                                @csrf @method('PATCH')
                                <select name="client_id" class="filter-input flex-1 min-w-0" aria-label="Cliente collegato">
                                    <option value="">— nessun cliente —</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" @selected($ticket->client_id === $client->id)>{{ $client->full_name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-secondary" type="submit">Collega</button>
                            </form>
                        @endcan
                    </div>
                </section>

                <section class="card">
                    <h2 class="card-title">Assegnazione</h2>
                    <div class="p-5 text-sm space-y-2">
                        <p>{{ $ticket->assignee?->name ?? 'Non assegnato' }}</p>
                        @can('reassign', App\Models\SupportTicket::class)
                            <form method="POST" action="{{ route('support-tickets.assign', $ticket) }}" class="space-y-2">
                                @csrf @method('PATCH')
                                <select name="user_id" class="filter-input w-full" aria-label="Assegna a">
                                    @foreach ($agents as $agent)
                                        <option value="{{ $agent->id }}" @selected($ticket->assigned_user_id === $agent->id)>{{ $agent->name }}{{ $agent->ticket_available ? '' : ' (non disponibile)' }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="note" maxlength="255" placeholder="Nota (facoltativa)" class="filter-input w-full">
                                <button class="btn btn-sm btn-secondary" type="submit">{{ $ticket->assignee ? 'Riassegna' : 'Assegna' }}</button>
                            </form>
                        @endcan
                        @if ($ticket->assignments()->exists())
                            <details class="text-xs text-slate-600"><summary class="cursor-pointer">Storico assegnazioni</summary>
                                <ul class="mt-1 space-y-1">
                                    @foreach ($ticket->assignments()->with(['user', 'previousUser', 'assignedBy'])->orderBy('id')->get() as $assignment)
                                        <li>{{ $assignment->created_at->format('d/m H:i') }} · {{ $assignment->previousUser?->name ?? '—' }} → {{ $assignment->user?->name }} ({{ $assignment->reason->label() }}@if ($assignment->assignedBy), da {{ $assignment->assignedBy->name }}@endif)@if ($assignment->note) — {{ $assignment->note }}@endif</li>
                                    @endforeach
                                </ul></details>
                        @endif
                    </div>
                </section>

                @if ($otherTickets->isNotEmpty())
                    <section class="card">
                        <h2 class="card-title">Altri ticket dello stesso cliente</h2>
                        <ul class="p-5 text-sm space-y-1">
                            @foreach ($otherTickets as $other)
                                <li><a class="link" href="{{ route('support-tickets.show', $other) }}">{{ $other->public_id }}</a> <span class="text-xs text-slate-600">· {{ $other->status->label() }} · {{ \Illuminate\Support\Str::limit($other->subject, 30) }}</span></li>
                            @endforeach
                        </ul>
                    </section>
                @endif

            </aside>
        </div>
    </div>

    <div id="saved-modal" class="hidden fixed inset-0 z-40 bg-slate-900/50 items-start justify-center p-6" role="dialog" aria-modal="true" aria-label="Risposte salvate">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-[85vh] flex flex-col">
            <div class="p-4 border-b border-slate-100 flex items-center gap-3">
                <input type="search" id="saved-search" class="filter-input flex-1" placeholder="Cerca nelle risposte salvate…" autocomplete="off">
                <button type="button" class="btn btn-sm btn-secondary" id="close-saved">Chiudi</button>
            </div>
            <ul id="saved-list" class="overflow-y-auto divide-y divide-slate-100 text-sm"></ul>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var body = document.getElementById('reply-body');
            if (!body) { return; }

            var csrf = document.querySelector('meta[name="csrf-token"]').content;
            var saved = @json($savedReplies);
            var modal = document.getElementById('saved-modal');
            var list = document.getElementById('saved-list');
            var search = document.getElementById('saved-search');
            var warning = document.getElementById('placeholder-warning');
            var badge = document.getElementById('draft-badge');
            var error = document.getElementById('draft-error');
            var sendButtons = document.querySelectorAll('#reply-form button[type=submit]');

            function checkPlaceholders() {
                var blocked = /\[\[DA COMPLETARE/i.test(body.value);
                warning.classList.toggle('hidden', !blocked);
                sendButtons.forEach(function (b) { b.disabled = blocked; });
            }

            function insertAtCursor(text, replaceAll) {
                if (replaceAll || body.value.trim() === '') {
                    body.value = text;
                } else {
                    var s = body.selectionStart, e = body.selectionEnd;
                    body.value = body.value.slice(0, s) + text + body.value.slice(e);
                    body.selectionStart = body.selectionEnd = s + text.length;
                }
                body.focus();
                checkPlaceholders();
            }

            function renderSaved() {
                var q = search.value.toLowerCase();
                list.innerHTML = '';
                saved.filter(function (r) { return !q || (r.title + ' ' + r.body).toLowerCase().indexOf(q) !== -1; }).forEach(function (r) {
                    var li = document.createElement('li');
                    li.className = 'p-3 hover:bg-slate-50';
                    var title = document.createElement('p');
                    title.className = 'font-medium';
                    title.textContent = r.title + ' · ' + r.scope;
                    var preview = document.createElement('p');
                    preview.className = 'text-xs text-slate-600 whitespace-pre-line';
                    preview.textContent = r.body.length > 200 ? r.body.slice(0, 200) + '…' : r.body;
                    var insert = document.createElement('button');
                    insert.type = 'button'; insert.className = 'btn btn-sm btn-primary mt-2'; insert.textContent = 'Inserisci';
                    insert.onclick = function () { insertAtCursor(r.body, false); document.getElementById('saved-reply-id').value = r.id; closeModal(); };
                    var replace = document.createElement('button');
                    replace.type = 'button'; replace.className = 'btn btn-sm btn-secondary mt-2 ml-2'; replace.textContent = 'Sostituisci tutto';
                    replace.onclick = function () {
                        if (body.value.trim() === '' || confirm('Sostituire il testo già scritto?')) {
                            insertAtCursor(r.body, true); document.getElementById('saved-reply-id').value = r.id; closeModal();
                        }
                    };
                    li.append(title, preview, insert, replace);
                    list.appendChild(li);
                });
                if (!list.children.length) { list.innerHTML = '<li class="p-4 text-slate-600">Nessuna risposta trovata.</li>'; }
            }

            function openModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); renderSaved(); search.focus(); }
            function closeModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); body.focus(); }

            document.getElementById('open-saved').addEventListener('click', openModal);
            document.getElementById('close-saved').addEventListener('click', closeModal);
            search.addEventListener('input', renderSaved);
            document.addEventListener('keydown', function (e) {
                if (e.ctrlKey && e.shiftKey && e.key.toLowerCase() === 'r') { e.preventDefault(); openModal(); }
                if (e.key === 'Escape') { closeModal(); }
            });

            body.addEventListener('input', checkPlaceholders);
            checkPlaceholders();

            // autosave of the unsent text
            var timer;
            body.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    fetch(@json(route('support-tickets.draft', $ticket)), {
                        method: 'PUT',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ body: body.value })
                    });
                }, 1500);
            });

            // "Salva come risposta"
            document.getElementById('save-as-reply').addEventListener('click', function () {
                var title = prompt('Titolo della nuova risposta salvata:');
                if (!title || body.value.trim() === '') { return; }
                var form = document.createElement('form');
                form.method = 'POST'; form.action = @json(route('support.saved-replies.from-text'));
                [['_token', csrf], ['title', title], ['body', body.value], ['support_ticket_id', @json($ticket->id)], ['support_ticket_type_id', @json($ticket->support_ticket_type_id)]].forEach(function (p) {
                    if (p[1] === null) { return; }
                    var input = document.createElement('input'); input.type = 'hidden'; input.name = p[0]; input.value = p[1]; form.appendChild(input);
                });
                document.body.appendChild(form); form.submit();
            });

            @if ($canDraft)
            var gen = document.getElementById('gen-draft');
            function generate(instruction) {
                if (body.value.trim() !== '' && !confirm('Sostituire il testo già scritto con la bozza?')) { return; }
                gen.disabled = true; gen.textContent = 'Genero la bozza…'; error.classList.add('hidden');
                sendButtons.forEach(function (b) { b.disabled = true; });
                fetch(@json(route('support-tickets.ai-draft', $ticket)), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ instruction: instruction || null })
                }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
                  .then(function (res) {
                      if (!res.ok) { throw new Error(res.json.message || 'Bozza non disponibile.'); }
                      body.value = res.json.text;
                      document.getElementById('ai-draft-id').value = res.json.id;
                      badge.dataset.id = res.json.id; badge.classList.remove('hidden');
                  })
                  .catch(function (e) { error.textContent = e.message; error.classList.remove('hidden'); })
                  .finally(function () { gen.disabled = false; gen.textContent = '✨ Rigenera bozza'; checkPlaceholders(); });
            }
            gen.addEventListener('click', function () { generate(document.getElementById('draft-instruction').value); });
            document.querySelectorAll('.quick-draft').forEach(function (b) {
                b.addEventListener('click', function () { document.getElementById('draft-instruction').value = b.dataset.text; generate(b.dataset.text); });
            });
            var suggested = document.getElementById('use-suggested');
            if (suggested) {
                suggested.addEventListener('click', function () {
                    insertAtCursor(suggested.dataset.text, true);
                    document.getElementById('ai-draft-id').value = suggested.dataset.id;
                    badge.dataset.id = suggested.dataset.id; badge.classList.remove('hidden');
                });
            }
            badge.querySelectorAll('[data-feedback]').forEach(function (b) {
                b.addEventListener('click', function () {
                    fetch(@json(url("assistenza/{$ticket->id}/bozza-ai")) + '/' + badge.dataset.id, {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ feedback: b.dataset.feedback })
                    });
                    b.textContent = '✔';
                });
            });
            @endif
        })();
    </script>
@endpush
