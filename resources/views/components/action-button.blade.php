{{-- One-button form for state-changing actions (DELETE, PATCH…) with optional confirmation. --}}
@props(['action', 'method' => 'POST', 'confirm' => null, 'variant' => 'btn-danger'])

<form action="{{ $action }}" method="POST" class="inline" @if ($confirm) onsubmit="return confirm(@js($confirm))" @endif>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif
    <button type="submit" {{ $attributes->merge(['class' => "btn btn-sm {$variant}"]) }}>{{ $slot }}</button>
</form>
