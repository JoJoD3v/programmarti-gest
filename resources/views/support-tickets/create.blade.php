@extends('layouts.support')

@section('title', 'Nuovo ticket email — ProgrammArti')
@section('page-title', 'Nuovo ticket email')

@section('content')
    <div class="max-w-3xl mx-auto mb-4">
        <x-back-link :href="route('support-tickets.index')">Ticket email</x-back-link>
    </div>

    @if ($mailboxes->isEmpty())
        <div class="max-w-3xl mx-auto px-4 py-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800">
            Nessuna casella attiva da cui inviare: collegane una da Assistenza email &gt; Caselle email.
        </div>
    @endif

    <x-form-card :action="route('support-tickets.store')" submit="Crea e invia email" :cancel="route('support-tickets.index')" width="max-w-3xl" files>
        <x-select name="mailbox_id" label="Invia da" :required="true" :value="$mailboxes->first()?->id"
                  :options="$mailboxes->mapWithKeys(fn ($mailbox) => [$mailbox->id => $mailbox->name.' <'.$mailbox->address.'>'])->all()" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <x-field name="customer_email" label="Email del cliente" type="email" required :value="$client?->email" />
            <x-field name="customer_name" label="Nome del cliente" :value="$client?->full_name" />
        </div>

        <x-field name="subject" label="Oggetto" required />

        <x-select name="support_ticket_type_id" label="Tipologia" placeholder="Da classificare"
                  :options="$types->pluck('name', 'id')->all()" />

        <x-textarea name="body" label="Messaggio" rows="10" required />
        @if ($signature)
            <p class="-mt-3 text-xs text-slate-600">La firma verrà aggiunta in fondo: «{{ \Illuminate\Support\Str::limit(str_replace('{dipendente_nome}', auth()->user()->first_name, $signature), 80) }}»</p>
        @endif

        <div>
            <input type="file" name="attachments[]" multiple class="text-sm" aria-label="Allegati">
            @error('attachments.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <p class="text-xs text-slate-600">L'email parte dalla casella scelta; le risposte del cliente arrivano in questo ticket. Se l'indirizzo è di un cliente in anagrafica, il ticket viene collegato.</p>
    </x-form-card>
@endsection
