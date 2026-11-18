@php
    $colors = ['slate' => 'Grigio', 'red' => 'Rosso', 'amber' => 'Ambra', 'green' => 'Verde', 'blue' => 'Blu', 'indigo' => 'Indaco', 'purple' => 'Viola', 'pink' => 'Rosa'];
@endphp

<x-field name="name" label="Nome" :value="$type->name" required />

<x-field name="slug" label="Codice" :value="$type->slug" :required="! $slugLocked" :readonly="$slugLocked" class="font-mono"
         :hint="$slugLocked ? 'Non modificabile: tipologia di sistema oppure già usata da dei ticket.' : 'Solo lettere minuscole, numeri e underscore (es. garanzia_estesa).'" />

<x-textarea name="description" label="Descrizione (istruzione per l'AI: quando usare questa tipologia)" :value="$type->description" rows="4" />
<p class="-mt-3 text-xs text-slate-600">Scrivi in modo chiaro in quali casi l'AI deve classificare un'email con questa tipologia: è il testo che legge per scegliere.</p>

<x-select name="color" label="Colore" :options="$colors" :value="$type->color" required />

<x-field name="sort" label="Posizione in elenco" type="number" :value="$type->sort" min="0" />

<fieldset class="border border-slate-200 rounded-lg p-4 space-y-3">
    <legend class="px-2 text-sm font-medium text-slate-700">Comportamento</legend>
    @foreach ([
        'assignable' => 'Assegnabile ai dipendenti (i ticket di questa tipologia vengono assegnati)',
        'active' => 'Attiva (selezionabile e usata dalla classificazione)',
    ] as $field => $label)
        <input type="hidden" name="{{ $field }}" value="0">
        <label class="flex items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-slate-300"
                   @checked(old($field, $type->{$field}))>
            {{ $label }}
        </label>
    @endforeach
</fieldset>
