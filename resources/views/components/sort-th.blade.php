{{--
    Sortable table header: toggles ?sort={column}&dir=asc|desc keeping the other filters.
    The active column comes from App\Support\Sort (request attributes), falling back to the query string.
--}}
@props(['column', 'class' => 'th'])

@php
    $sort = request()->attributes->get('sort', request()->query('sort'));
    $dir = request()->attributes->get('dir', request()->query('dir'));
    $active = $sort === $column;
    $url = request()->fullUrlWithQuery(['sort' => $column, 'dir' => $active && $dir === 'asc' ? 'desc' : 'asc', 'page' => null]);
@endphp

<th {{ $attributes->merge(['class' => $class]) }} @if ($active) aria-sort="{{ $dir === 'asc' ? 'ascending' : 'descending' }}" @endif>
    <a href="{{ $url }}" class="group inline-flex items-center gap-1 whitespace-nowrap {{ $active ? 'text-indigo-600' : 'hover:text-slate-800' }}">
        {{ $slot }}
        @if ($active)
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $dir === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/>
            </svg>
        @else
            <svg class="w-3 h-3 opacity-25 group-hover:opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4M16 15l-4 4-4-4"/>
            </svg>
        @endif
    </a>
</th>
