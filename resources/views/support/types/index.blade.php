@extends('layouts.support')

@section('title', 'Tipologie ticket — ProgrammArti')
@section('page-title', 'Tipologie ticket')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-slate-600">{{ $types->count() }} tipologie</p>
        <a href="{{ route('support.types.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Nuova Tipologia
        </a>
    </div>

    <x-table :items="$types" empty="Nessuna tipologia.">
        <x-slot:head>
            <th class="th">Nome</th>
            <th class="th">Assegnabile</th>
            <th class="th">Attiva</th>
            <th class="th">Ticket</th>
            <th class="th text-right">Azioni</th>
        </x-slot:head>

        @foreach ($types as $type)
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="td">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {{ $type->badgeClass() }}">{{ $type->name }}</span>
                    @if ($type->is_system)
                        <span class="ml-1 text-xs text-slate-600">(di sistema)</span>
                    @endif
                </td>
                <td class="td text-slate-700">{{ $type->assignable ? 'Sì' : 'No' }}</td>
                <td class="td">
                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $type->active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-700' }}">
                        {{ $type->active ? 'Attiva' : 'Disattiva' }}
                    </span>
                </td>
                <td class="td text-slate-700">{{ $type->tickets_count }}</td>
                <td class="td text-right whitespace-nowrap">
                    <a href="{{ route('support.types.edit', $type) }}" class="btn btn-sm btn-secondary">Modifica</a>
                    @unless ($type->is_system || $type->tickets_count > 0)
                        <x-action-button :action="route('support.types.destroy', $type)" method="DELETE"
                                         confirm="Eliminare questa tipologia?">Elimina</x-action-button>
                    @endunless
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection
