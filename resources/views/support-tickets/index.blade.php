@extends('layouts.support')

@section('title', 'Ticket email — ProgrammArti')
@section('page-title', 'Ticket email')

@section('content')
    @php
        $isAdmin = auth()->user()->isTicketingAdmin();
        $tabs = $isAdmin
            ? ['open' => 'Aperti', 'pending' => 'In attesa del cliente', 'closed' => 'Chiusi', 'all' => 'Tutti']
            : ['open' => 'I miei aperti', 'pending' => 'In attesa del cliente', 'closed' => 'Chiusi'];
    @endphp

    <div class="space-y-5">
        @if ($counters)
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('support-tickets.index', ['tab' => 'open']) }}" class="card p-4 hover:border-indigo-300">
                    <p class="text-xs text-slate-600">Aperti</p><p class="text-2xl font-semibold">{{ $counters['open'] }}</p>
                </a>
                <a href="{{ route('support-tickets.index', ['tab' => 'pending']) }}" class="card p-4 hover:border-indigo-300">
                    <p class="text-xs text-slate-600">In attesa del cliente</p><p class="text-2xl font-semibold">{{ $counters['pending'] }}</p>
                </a>
                <a href="{{ route('support-tickets.index', ['user' => 'none']) }}" class="card p-4 hover:border-indigo-300">
                    <p class="text-xs text-slate-600">Non assegnati</p><p class="text-2xl font-semibold {{ $counters['unassigned'] ? 'text-amber-600' : '' }}">{{ $counters['unassigned'] }}</p>
                </a>
                <a href="{{ route('support-tickets.index', ['type' => 'none']) }}" class="card p-4 hover:border-indigo-300">
                    <p class="text-xs text-slate-600">Da classificare</p><p class="text-2xl font-semibold {{ $counters['unclassified'] ? 'text-amber-600' : '' }}">{{ $counters['unclassified'] }}</p>
                </a>
            </div>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-2">
            <nav class="flex flex-wrap gap-2" aria-label="Filtri stato">
                @foreach ($tabs as $key => $label)
                    <a href="{{ route('support-tickets.index', array_merge(request()->except('page', 'tab'), ['tab' => $key])) }}"
                       @class(['btn btn-sm', 'btn-primary' => $tab === $key, 'btn-secondary' => $tab !== $key])>{{ $label }}</a>
                @endforeach
            </nav>
            <a href="{{ route('support-tickets.create') }}" class="btn btn-sm btn-primary">+ Nuovo ticket</a>
        </div>

        <form method="GET" action="{{ route('support-tickets.index') }}" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <x-sort-inputs />
            <input type="search" name="q" value="{{ $q }}" placeholder="Email, nome, oggetto, T-1042…" class="filter-input min-w-56">
            @if ($mailboxes->count() > 1)
                <select name="mailbox" class="filter-input" onchange="this.form.submit()">
                    <option value="">Tutte le caselle</option>
                    @foreach ($mailboxes as $mailbox)
                        <option value="{{ $mailbox->id }}" @selected($mailboxFilter === (string) $mailbox->id)>{{ $mailbox->address }}</option>
                    @endforeach
                </select>
            @endif
            <select name="type" class="filter-input" onchange="this.form.submit()">
                <option value="">Tutte le tipologie</option>
                <option value="none" @selected($typeFilter === 'none')>Da classificare</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected($typeFilter === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
            @if ($isAdmin)
                <select name="user" class="filter-input" onchange="this.form.submit()">
                    <option value="">Tutti i dipendenti</option>
                    <option value="none" @selected($userFilter === 'none')>Non assegnati</option>
                    @foreach ($agents as $agent)
                        <option value="{{ $agent->id }}" @selected($userFilter === (string) $agent->id)>{{ $agent->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="filter-input" aria-label="Dal">
                <input type="date" name="to" value="{{ request('to') }}" class="filter-input" aria-label="Al">
            @endif
            <button class="btn btn-secondary btn-sm" type="submit">Cerca</button>
            @if ($q || $typeFilter || $userFilter || $mailboxFilter || request('from') || request('to'))
                <a href="{{ route('support-tickets.index', ['tab' => $tab]) }}" class="text-sm text-slate-600 hover:underline">Azzera</a>
            @endif
        </form>

        <form method="POST" action="{{ route('support-tickets.bulk') }}" id="bulk-form">
            @csrf
            <x-table :items="$tickets" empty="Nessun ticket trovato.">
                <x-slot:head>
                    @if ($isAdmin)<th class="th w-8"><input type="checkbox" id="select-all" aria-label="Seleziona tutti"></th>@endif
                    <x-sort-th column="ticket">Ticket</x-sort-th>
                    <x-sort-th column="customer">Cliente / oggetto</x-sort-th>
                    <x-sort-th column="type">Tipologia</x-sort-th>
                    <x-sort-th column="mailbox">Casella</x-sort-th>
                    @if ($isAdmin)<x-sort-th column="assignee">Assegnato a</x-sort-th>@endif
                    <x-sort-th column="status">Stato</x-sort-th>
                    <x-sort-th column="last">Ultimo messaggio</x-sort-th>
                </x-slot:head>

                @foreach ($tickets as $ticket)
                    <tr class="hover:bg-slate-50 cursor-pointer" data-href="{{ route('support-tickets.show', $ticket) }}">
                        @if ($isAdmin)<td class="td"><input type="checkbox" name="ids[]" value="{{ $ticket->id }}" class="row-check" aria-label="Seleziona {{ $ticket->public_id }}"></td>@endif
                        <td class="td whitespace-nowrap">
                            <a href="{{ route('support-tickets.show', $ticket) }}" class="link font-medium">{{ $ticket->public_id }}</a>
                            @if ($ticket->status->value === 'open' && $ticket->last_inbound_at && ! $ticket->last_outbound_at?->gt($ticket->last_inbound_at))
                                <span class="ml-1 inline-flex px-1.5 rounded bg-red-100 text-red-700 text-xs" title="Il cliente attende una risposta">nuova</span>
                            @endif
                        </td>
                        <td class="td max-w-md">
                            <p class="font-medium truncate">
                                {{ $ticket->customer_name ?: $ticket->customer_email }}
                                @if ($ticket->client)<span class="ml-1 inline-flex px-1.5 rounded bg-blue-100 text-blue-800 text-xs" title="Cliente in anagrafica: {{ $ticket->client->full_name }}">cliente</span>@endif
                            </p>
                            <p class="text-xs text-slate-600 truncate">{{ $ticket->subject }}</p>
                        </td>
                        <td class="td">
                            @if ($ticket->type)
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $ticket->type->badgeClass() }}">{{ $ticket->type->name }}
                                    @if ($ticket->type_source?->value === 'manual')<span title="Impostata a mano">✎</span>@endif</span>
                            @else
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">Da classificare</span>
                            @endif
                        </td>
                        <td class="td text-xs text-slate-600">{{ $ticket->mailbox?->address ?? '—' }}</td>
                        @if ($isAdmin)<td class="td">{{ $ticket->assignee?->name ?? '—' }}</td>@endif
                        <td class="td"><x-badge :status="$ticket->status" /></td>
                        <td class="td whitespace-nowrap text-slate-600">{{ ($ticket->last_inbound_at ?? $ticket->created_at)->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </x-table>

            @if ($isAdmin && $tickets->isNotEmpty())
                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-slate-600">Con i selezionati:</span>
                    <select name="action" id="bulk-action" class="filter-input">
                        <option value="assign">Assegna a…</option>
                        <option value="distribute">Distribuisci automaticamente</option>
                        <option value="type">Cambia tipologia…</option>
                        <option value="spam">Chiudi come spam</option>
                    </select>
                    <select name="user_id" class="filter-input" data-for="assign">
                        @foreach ($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach
                    </select>
                    <select name="support_ticket_type_id" class="filter-input" data-for="type" hidden>
                        @foreach ($types as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Applicare l\'azione ai ticket selezionati?')">Applica</button>
                </div>
            @endif
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var all = document.getElementById('select-all');
            if (all) {
                all.addEventListener('change', function () {
                    document.querySelectorAll('.row-check').forEach(function (box) { box.checked = all.checked; });
                });
            }
            var action = document.getElementById('bulk-action');
            if (action) {
                var sync = function () {
                    document.querySelectorAll('[data-for]').forEach(function (el) { el.hidden = el.dataset.for !== action.value; });
                };
                action.addEventListener('change', sync);
                sync();
            }
        })();
    </script>
@endpush
