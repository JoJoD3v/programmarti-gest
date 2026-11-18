@extends('layouts.support')

@section('title', 'Nuova Risposta Salvata — ProgrammArti')
@section('page-title', 'Nuova risposta salvata')

@section('content')
    <div class="max-w-2xl mx-auto mb-4">
        <x-back-link :href="route('support.saved-replies.index')">Risposte salvate</x-back-link>
    </div>

    <x-form-card :action="route('support.saved-replies.store')" submit="Crea Risposta" :cancel="route('support.saved-replies.index')" width="max-w-3xl">
        @include('support.saved-replies._form')
    </x-form-card>
@endsection
