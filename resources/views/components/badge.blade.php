{{-- Status badge for enums exposing label() and badgeClass(). --}}
@props(['status'])

@if ($status)
    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {{ $status->badgeClass() }}">{{ $status->label() }}</span>
@else
    <span class="text-slate-600">—</span>
@endif
