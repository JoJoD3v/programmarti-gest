@extends('layouts.support')

@section('title', 'Modifica Tipologia — ProgrammArti')
@section('page-title', 'Modifica Tipologia')

@section('content')
    <div class="max-w-2xl mx-auto mb-4">
        <x-back-link :href="route('support.types.index')">Tipologie</x-back-link>
    </div>

    <x-form-card :action="route('support.types.update', $type)" method="PUT" submit="Salva Modifiche" :cancel="route('support.types.index')">
        @include('support.types._form')
    </x-form-card>
@endsection
