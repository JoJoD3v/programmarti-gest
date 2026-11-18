@extends('layouts.support')

@section('title', 'Informazioni per le risposte AI — ProgrammArti')
@section('page-title', 'Informazioni per le risposte AI')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-slate-600">{{ $entries->count() }} informazioni</p>
        <a href="{{ route('support.knowledge.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nuova Informazione
        </a>
    </div>

    <x-table :items="$entries" empty="Nessuna informazione inserita.">
        <x-slot:head>
            <th class="th">Titolo</th>
            <th class="th">Tipologie</th>
            <th class="th">Anteprima</th>
            <th class="th">Stato</th>
            <th class="th text-right">Azioni</th>
        </x-slot:head>

        @foreach ($entries as $entry)
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="td font-medium text-slate-800">{{ $entry->title }}</td>
                <td class="td text-slate-700">
                    @if (empty($entry->ticket_type_ids))
                        Tutte
                    @else
                        {{ collect($entry->ticket_type_ids)->map(fn ($id) => $typeNames[$id] ?? '—')->implode(', ') }}
                    @endif
                </td>
                <td class="td text-slate-600 max-w-md">{{ \Illuminate\Support\Str::limit($entry->content, 120) }}</td>
                <td class="td">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $entry->active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-700' }}">
                        {{ $entry->active ? 'Attiva' : 'Disattiva' }}
                    </span>
                </td>
                <td class="td text-right whitespace-nowrap">
                    <a href="{{ route('support.knowledge.edit', $entry) }}" class="btn btn-sm btn-secondary">Modifica</a>
                    <x-action-button :action="route('support.knowledge.destroy', $entry)" method="DELETE"
                                     confirm="Eliminare questa informazione?">Elimina</x-action-button>
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection
