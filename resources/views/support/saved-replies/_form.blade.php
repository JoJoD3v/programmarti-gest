@php
    $typeOptions = $types->mapWithKeys(fn ($type) => [$type->id => $type->name])->all();
    $scopeValue = $reply->scope instanceof \BackedEnum ? $reply->scope->value : $reply->scope;
@endphp

<x-field name="title" label="Titolo" :value="$reply->title" required />

<div>
    <x-textarea name="body" label="Testo della risposta" :value="$reply->body" rows="10" required />
    <div class="mt-2 rounded-lg bg-slate-50 border border-slate-200 p-3">
        <p class="text-xs font-medium text-slate-700 mb-1">Variabili disponibili</p>
        <ul class="text-xs text-slate-600 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-0.5">
            @foreach ($variables as $variable => $description)
                <li><code class="font-mono text-slate-800">{{ $variable }}</code> — {{ $description }}</li>
            @endforeach
        </ul>
    </div>
</div>

<x-select name="support_ticket_type_id" label="Tipologia" :options="$typeOptions" :value="$reply->support_ticket_type_id" placeholder="Tutte le tipologie" />

@if ($reply->exists)
    <div>
        <span class="block text-sm font-medium text-slate-700 mb-1.5">Ambito</span>
        <p class="text-sm text-slate-700">{{ $reply->scope->label() }}</p>
    </div>
@else
    <x-select name="scope" label="Ambito" :options="$scopeOptions" :value="$scopeValue" required />
@endif

<x-field name="sort" label="Posizione in elenco" type="number" :value="$reply->sort" min="0" />

<div>
    <input type="hidden" name="active" value="0">
    <label class="flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="active" value="1" class="rounded border-slate-300" @checked(old('active', $reply->active))>
        Attiva
    </label>
</div>
