@extends('layouts.support')

@section('title', 'Modifica Informazione — ProgrammArti')
@section('page-title', 'Modifica informazione per le risposte AI')

@section('content')
    <div class="max-w-2xl mx-auto mb-4">
        <x-back-link :href="route('support.knowledge.index')">Informazioni per le risposte AI</x-back-link>
    </div>

    <x-form-card :action="route('support.knowledge.update', $entry)" method="PUT" submit="Salva Modifiche" :cancel="route('support.knowledge.index')" width="max-w-3xl">
        @include('support.knowledge._form')
    </x-form-card>
@endsection
