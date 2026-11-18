{{-- "Back to list" link shown at the top left of detail pages. --}}
@props(['href'])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'btn btn-sm btn-back']) }}>
    <svg class="w-3.5 h-3.5 -ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
    </svg>
    {{ $slot }}
</a>
