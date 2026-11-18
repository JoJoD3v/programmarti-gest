@props([
    'name',
    'label',
    'required' => false,
    'value' => null,
    'rows' => 3,
])

@php $id = str_replace('_', '-', $name); @endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700 mb-1.5">
        {{ $label }} @if ($required)<span class="text-red-500">*</span>@endif
    </label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
              {{ $attributes->class([
                  'w-full px-4 py-2.5 border rounded-lg text-slate-800 text-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                  'border-red-400 bg-red-50' => $errors->has($name),
                  'border-slate-300' => ! $errors->has($name),
              ]) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
