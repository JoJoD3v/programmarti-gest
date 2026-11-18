{{-- Labelled input with old() value and validation error. --}}
@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'value' => null,
    'hint' => null,
])

@php
    $id = str_replace(['_', '[', ']'], ['-', '-', ''], $name);
    $dotName = str_replace(['[', ']'], ['.', ''], $name);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 mb-1.5">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           @if ($type !== 'file' && $type !== 'password') value="{{ old($dotName, $value) }}" @endif
           @required($required)
           {{ $attributes->class([
               'w-full px-4 py-2.5 border rounded-lg text-slate-800 text-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
               'border-red-400 bg-red-50' => $errors->has($dotName),
               'border-slate-300' => ! $errors->has($dotName),
           ]) }}>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-600">{{ $hint }}</p>
    @endif
    @error($dotName)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
