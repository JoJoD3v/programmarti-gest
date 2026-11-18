{{-- Labelled select. $options: [value => label]. --}}
@props([
    'name',
    'label',
    'options' => [],
    'required' => false,
    'value' => null,
    'placeholder' => null,
])

@php
    $id = str_replace('_', '-', $name);
    $selected = (string) old($name, $value instanceof \BackedEnum ? $value->value : $value);
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 mb-1.5">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <select id="{{ $id }}" name="{{ $name }}" @required($required)
            {{ $attributes->class([
                'w-full px-4 py-2.5 border rounded-lg text-slate-800 text-sm bg-white transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                'border-red-400 bg-red-50' => $errors->has($name),
                'border-slate-300' => ! $errors->has($name),
            ]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
