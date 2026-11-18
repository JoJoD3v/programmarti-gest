@extends('layouts.support')

@section('title', 'Regole mittente — ProgrammArti')
@section('page-title', 'Regole mittente')

@section('content')
    @php
        $updateErrors = $errors->getBag('rule_update');
        $activeTypes = $types->where('active', true)->mapWithKeys(fn ($type) => [$type->id => $type->name])->all();
    @endphp

    <p class="text-sm text-slate-600 mb-4">
        Le email provenienti da un indirizzo o da un dominio indicato qui ricevono sempre la tipologia scelta, senza passare dalla classificazione automatica.
    </p>

    @if ($updateErrors->any())
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
            <ul class="list-disc list-inside">
                @foreach ($updateErrors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Nuova regola --}}
    <section class="card mb-6">
        <div class="card-title">Nuova regola</div>
        <form action="{{ route('support.rules.store') }}" method="POST" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-field name="pattern" label="Indirizzo o dominio" placeholder="mario@esempio.it oppure @esempio.it" required
                         hint="Indirizzo completo oppure dominio che inizia con @." />
                <x-select name="support_ticket_type_id" label="Tipologia" :options="$activeTypes" placeholder="Scegli…" required />
                <x-field name="note" label="Note" />
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <input type="hidden" name="active" value="0">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="active" value="1" class="rounded border-slate-300" @checked(old('active', true))>
                        Attiva
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">Aggiungi regola</button>
            </div>
        </form>
    </section>

    {{-- Regole esistenti --}}
    <x-table :items="$rules" title="Regole esistenti" empty="Nessuna regola definita.">
        <x-slot:head>
            <th class="th">Mittente</th>
            <th class="th">Tipologia</th>
            <th class="th">Note</th>
            <th class="th">Attiva</th>
            <th class="th text-right">Azioni</th>
        </x-slot:head>

        @foreach ($rules as $rule)
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="td font-mono text-slate-800">
                    {{ $rule->pattern }}
                    <form id="rule-form-{{ $rule->id }}" action="{{ route('support.rules.update', $rule) }}" method="POST">
                        @csrf
                        @method('PUT')
                    </form>
                </td>
                <td class="td">
                    <select name="support_ticket_type_id" form="rule-form-{{ $rule->id }}"
                            class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-slate-800 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach ($types as $type)
                            @if ($type->active || $type->id === $rule->support_ticket_type_id)
                                <option value="{{ $type->id }}" @selected($type->id === $rule->support_ticket_type_id)>{{ $type->name }}{{ $type->active ? '' : ' (disattivata)' }}</option>
                            @endif
                        @endforeach
                    </select>
                </td>
                <td class="td">
                    <input type="text" name="note" form="rule-form-{{ $rule->id }}" value="{{ $rule->note }}" maxlength="255"
                           class="w-full px-3 py-1.5 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </td>
                <td class="td">
                    <input type="hidden" name="active" value="0" form="rule-form-{{ $rule->id }}">
                    <input type="checkbox" name="active" value="1" form="rule-form-{{ $rule->id }}" class="rounded border-slate-300"
                           @checked($rule->active)>
                </td>
                <td class="td text-right whitespace-nowrap">
                    <button type="submit" form="rule-form-{{ $rule->id }}" class="btn btn-sm btn-primary">Salva</button>
                    <x-action-button :action="route('support.rules.destroy', $rule)" method="DELETE"
                                     confirm="Eliminare questa regola?">Elimina</x-action-button>
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection
