@extends('layouts.support')

@section('title', 'Nuova Tipologia — ProgrammArti')
@section('page-title', 'Nuova Tipologia')

@section('content')
    <div class="max-w-2xl mx-auto mb-4">
        <x-back-link :href="route('support.types.index')">Tipologie</x-back-link>
    </div>

    <x-form-card :action="route('support.types.store')" submit="Crea Tipologia" :cancel="route('support.types.index')">
        @include('support.types._form')
    </x-form-card>
@endsection
