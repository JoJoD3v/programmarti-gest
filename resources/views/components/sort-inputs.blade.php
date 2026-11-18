{{-- Keeps the current column sorting when a filter form is submitted. --}}
@foreach (['sort', 'dir'] as $key)
    @if (request()->filled($key))
        <input type="hidden" name="{{ $key }}" value="{{ request()->query($key) }}">
    @endif
@endforeach
