@extends('layouts.support')

@section('title', 'Modifica Risposta Salvata — ProgrammArti')
@section('page-title', 'Modifica risposta salvata')

@section('content')
    <div class="max-w-2xl mx-auto mb-4">
        <x-back-link :href="route('support.saved-replies.index')">Risposte salvate</x-back-link>
    </div>

    <x-form-card :action="route('support.saved-replies.update', $reply)" method="PUT" submit="Salva Modifiche" :cancel="route('support.saved-replies.index')" width="max-w-3xl">
        @include('support.saved-replies._form')
    </x-form-card>

    <div class="max-w-3xl mx-auto mt-6">
        <section class="card">
            <div class="card-title">Anteprima</div>
            <div class="p-5">
                <p class="text-xs text-slate-600 mb-2">Variabili sostituite con valori d'esempio (testo attualmente salvato o appena inserito).</p>
                <div class="whitespace-pre-line text-sm text-slate-800 bg-slate-50 border border-slate-200 rounded-lg p-4">{{ $preview }}</div>
            </div>
        </section>
    </div>
@endsection
