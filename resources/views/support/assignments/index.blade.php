@extends('layouts.support')

@section('title', 'Assegnazioni — Ticket email')
@section('page-title', 'Assegnazioni ticket')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <section class="card">
            <h2 class="card-title flex items-center justify-between">
                <span>Carico per dipendente</span>
                <span class="text-xs font-normal text-slate-600">Chiusi negli ultimi
                    @foreach ([7, 30, 90] as $d)
                        <a href="{{ route('support.assignments.index', ['days' => $d]) }}" @class(['underline' => $days !== $d, 'font-semibold' => $days === $d])>{{ $d }}g</a>@unless ($loop->last) · @endunless
                    @endforeach
                </span>
            </h2>
            @if ($agents->isEmpty())
                <p class="p-5 text-sm text-slate-600">Nessun dipendente abilitato. Abilita la gestione ticket da Dipendenti › Modifica.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr><th class="th">Dipendente</th><th class="th">Aperti</th><th class="th">In attesa</th><th class="th">Chiusi ({{ $days }}g)</th><th class="th">Disponibile</th><th class="th">Azioni</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($agents as $agent)
                                <tr>
                                    <td class="td font-medium">{{ $agent->name }}</td>
                                    <td class="td">{{ $agent->open_count }}</td>
                                    <td class="td">{{ $agent->pending_count }}</td>
                                    <td class="td">{{ $agent->closed_count }}</td>
                                    <td class="td">
                                        <form method="POST" action="{{ route('support.assignments.availability', $agent) }}" class="flex items-center gap-2">
                                            @csrf @method('PATCH')
                                            <span @class(['inline-flex px-2 py-0.5 rounded-full text-xs', 'bg-green-100 text-green-800' => $agent->ticket_available, 'bg-slate-200 text-slate-700' => ! $agent->ticket_available])>{{ $agent->ticket_available ? 'Sì' : 'No' }}</span>
                                            @if ($agent->ticket_available)
                                                <label class="text-xs flex items-center gap-1"><input type="checkbox" name="redistribute" value="1"> ridistribuisci i suoi ticket</label>
                                            @endif
                                            <button class="btn btn-sm btn-secondary" type="submit">{{ $agent->ticket_available ? 'Segna assente' : 'Segna presente' }}</button>
                                        </form>
                                    </td>
                                    <td class="td">
                                        @if ($agent->open_count + $agent->pending_count > 0)
                                            <form method="POST" action="{{ route('support.assignments.redistribute', $agent) }}" class="flex items-center gap-2" onsubmit="return confirm('Spostare i ticket aperti di {{ $agent->name }}?')">
                                                @csrf
                                                <select name="to" class="filter-input" aria-label="Destinatario">
                                                    <option value="">Distribuisci in automatico</option>
                                                    @foreach ($agents->where('id', '!=', $agent->id) as $other)<option value="{{ $other->id }}">{{ $other->name }}</option>@endforeach
                                                </select>
                                                <button class="btn btn-sm btn-warning" type="submit">Sposta ticket</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-slate-600">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($orphans->isNotEmpty())
            <div class="px-4 py-3 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
                Ticket aperti assegnati a dipendenti non più abilitati:
                @foreach ($orphans as $orphan)
                    {{ $orphan->name }} ({{ $orphan->open_count }})
                    <form method="POST" action="{{ route('support.assignments.redistribute', $orphan) }}" class="inline">@csrf<button class="underline">ridistribuisci</button></form>
                @endforeach
            </div>
        @endif

        <section class="card">
            <h2 class="card-title">Non assegnati ({{ $unassigned->count() }})</h2>
            @if ($unassigned->isEmpty())
                <p class="p-5 text-sm text-slate-600">Tutti i ticket aperti hanno un assegnatario.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b border-slate-100"><tr><th class="th">Ticket</th><th class="th">Tipologia</th><th class="th">Mittente</th><th class="th">Oggetto</th><th class="th">Età</th><th class="th">Assegna</th></tr></thead>
                        <tbody class="divide-y divide-slate-50">
                            @foreach ($unassigned as $ticket)
                                <tr>
                                    <td class="td"><a class="link" href="{{ route('support-tickets.show', $ticket) }}">{{ $ticket->public_id }}</a></td>
                                    <td class="td">{{ $ticket->type?->name ?? 'Da classificare' }}@if ($ticket->type && ! $ticket->type->assignable) <span class="text-xs text-slate-600">(non assegnabile)</span>@endif</td>
                                    <td class="td">{{ $ticket->customer_email }}</td>
                                    <td class="td max-w-xs truncate">{{ $ticket->subject }}</td>
                                    <td class="td whitespace-nowrap">{{ $ticket->created_at->diffForHumans(null, true) }}</td>
                                    <td class="td">
                                        <form method="POST" action="{{ route('support-tickets.assign', $ticket) }}" class="flex gap-2">
                                            @csrf @method('PATCH')
                                            <select name="user_id" class="filter-input" aria-label="Assegna a">
                                                @foreach ($agents as $agent)<option value="{{ $agent->id }}">{{ $agent->name }}</option>@endforeach
                                            </select>
                                            <button class="btn btn-sm btn-secondary" type="submit">Assegna</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection
