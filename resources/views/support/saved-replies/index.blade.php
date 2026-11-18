@extends('layouts.support')

@section('title', 'Risposte salvate — ProgrammArti')
@section('page-title', 'Risposte salvate')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-slate-600">{{ $replies->total() }} risposte</p>
        @if ($canCreate)
            <a href="{{ route('support.saved-replies.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Nuova Risposta
            </a>
        @endif
    </div>

    @unless ($personalEnabled)
        <div class="mb-4 px-4 py-3 bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg">
            Le risposte personali sono disattivate dalle impostazioni: sono disponibili solo quelle globali.
        </div>
    @endunless

    {{-- Filtri --}}
    <form method="GET" action="{{ route('support.saved-replies.index') }}" class="mb-4">
        <div class="flex flex-wrap items-center gap-3">
            <input type="search" name="q" value="{{ $q }}" placeholder="Cerca nel titolo o nel testo…" autocomplete="off"
                   class="flex-1 min-w-48 max-w-sm px-4 py-2 text-sm bg-white border border-slate-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition">
            <select name="type" onchange="this.form.submit()"
                    class="py-2 px-3 text-sm bg-white border border-slate-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition">
                <option value="">Tutte le tipologie</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected($typeFilter === (string) $type->id)>{{ $type->name }}</option>
                @endforeach
            </select>
            <select name="scope" onchange="this.form.submit()"
                    class="py-2 px-3 text-sm bg-white border border-slate-200 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition">
                <option value="">Tutti gli ambiti</option>
                <option value="global" @selected($scopeFilter === 'global')>Globali</option>
                @if ($personalEnabled)
                    <option value="personal" @selected($scopeFilter === 'personal')>Personali</option>
                @endif
            </select>
            <button type="submit"
                    class="px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm text-slate-600 hover:bg-slate-50 shadow-sm transition">
                Cerca
            </button>
            @if ($q !== '' || $typeFilter !== '' || $scopeFilter !== '')
                <a href="{{ route('support.saved-replies.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-700 transition">Azzera</a>
            @endif
        </div>
    </form>

    <x-table :items="$replies" empty="Nessuna risposta salvata.">
        <x-slot:head>
            <th class="th">Titolo</th>
            <th class="th">Tipologia</th>
            <th class="th">Ambito</th>
            <th class="th">Utilizzi</th>
            <th class="th">Ultimo utilizzo</th>
            <th class="th text-right">Azioni</th>
        </x-slot:head>

        @foreach ($replies as $reply)
            @php $isGlobal = $reply->scope === \App\Ticketing\Enums\SavedReplyScope::Global; @endphp
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="td">
                    <p class="font-medium text-slate-800">{{ $reply->title }}
                        @unless ($reply->active)
                            <span class="ml-1 text-xs font-normal text-slate-600">(disattiva)</span>
                        @endunless
                    </p>
                    <p class="text-xs text-slate-600">{{ \Illuminate\Support\Str::limit($reply->body, 90) }}</p>
                </td>
                <td class="td text-slate-700">{{ $reply->type?->name ?? 'Tutte' }}</td>
                <td class="td">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $isGlobal ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $reply->scope->label() }}
                    </span>
                    @if (! $isGlobal && auth()->user()->isTicketingAdmin() && $reply->owner)
                        <span class="block text-xs text-slate-600 mt-0.5">{{ $reply->owner->first_name }} {{ $reply->owner->last_name }}</span>
                    @endif
                </td>
                <td class="td text-slate-700">{{ $reply->usage_count }}</td>
                <td class="td text-slate-600">{{ $reply->last_used_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="td text-right whitespace-nowrap">
                    @if (in_array($reply->id, $editableIds, true))
                        <a href="{{ route('support.saved-replies.edit', $reply) }}" class="btn btn-sm btn-secondary">Modifica</a>
                    @endif
                    @if ($personalEnabled || ($isGlobal && auth()->user()->isTicketingAdmin()))
                        <x-action-button :action="route('support.saved-replies.duplicate', $reply)" variant="btn-secondary">Duplica</x-action-button>
                    @endif
                    @if (! $isGlobal && auth()->user()->isTicketingAdmin())
                        <x-action-button :action="route('support.saved-replies.promote', $reply)" variant="btn-warning"
                                         confirm="Rendere globale questa risposta? Sarà visibile a tutti i dipendenti abilitati.">Promuovi</x-action-button>
                    @endif
                    @if (in_array($reply->id, $editableIds, true))
                        <x-action-button :action="route('support.saved-replies.destroy', $reply)" method="DELETE"
                                         confirm="Eliminare questa risposta salvata?">Elimina</x-action-button>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection
