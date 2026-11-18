@php
    $selectedTypeIds = array_map('intval', (array) old('ticket_type_ids', $entry->ticket_type_ids ?? []));
@endphp

<x-field name="title" label="Titolo" :value="$entry->title" required />

<x-textarea name="content" label="Contenuto" :value="$entry->content" rows="12" required />
<p class="-mt-3 text-xs text-slate-600">Informazioni che l'AI può usare per scrivere le bozze di risposta: orari, politiche di reso, tempi di spedizione, contatti.</p>

<fieldset class="border border-slate-200 rounded-lg p-4 space-y-2">
    <legend class="px-2 text-sm font-medium text-slate-700">Si applica alle tipologie</legend>
    <p class="text-xs text-slate-600">Nessuna spunta = vale per tutte le tipologie.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        @foreach ($types as $type)
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="checkbox" name="ticket_type_ids[]" value="{{ $type->id }}" class="rounded border-slate-300"
                       @checked(in_array($type->id, $selectedTypeIds, true))>
                {{ $type->name }}
            </label>
        @endforeach
    </div>
    @error('ticket_type_ids')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</fieldset>

<x-field name="sort" label="Posizione in elenco" type="number" :value="$entry->sort" min="0" />

<div>
    <input type="hidden" name="active" value="0">
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="active" value="1" class="rounded border-slate-300" @checked(old('active', $entry->active))>
        Attiva
    </label>
</div>
