{{-- List card: table with header slot, empty state and pagination links. --}}
@props(['items', 'empty' => 'Nessun elemento.', 'title' => null])

<section {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    @if ($title)
        <div class="card-title flex items-center gap-2">
            {{ $title }}
            @if ($items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $items->total() }}</span>
            @endif
        </div>
    @endif

    @if ($items->isEmpty())
        <p class="py-12 text-center text-slate-600 text-sm">{{ $empty }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>{{ $head }}</tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    {{ $slot }}
                </tbody>
            </table>
        </div>

        @if ($items instanceof \Illuminate\Contracts\Pagination\Paginator && $items->hasPages())
            <div class="px-5 py-3 border-t border-slate-100">
                {{ $items->links() }}
            </div>
        @endif
    @endif
</section>
