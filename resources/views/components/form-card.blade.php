{{-- Standard form page: card, CSRF, method spoofing, cancel link and submit button. --}}
@props([
    'action',
    'method' => 'POST',
    'submit' => 'Salva',
    'cancel' => null,
    'files' => false,
    'width' => 'max-w-2xl',
])

<div class="{{ $width }} mx-auto">
    <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
        <form action="{{ $action }}" method="POST" @if ($files) enctype="multipart/form-data" @endif
              {{ $attributes->merge(['class' => 'p-6 space-y-5']) }}>
            @csrf
            @if (strtoupper($method) !== 'POST')
                @method($method)
            @endif

            {{ $slot }}

            <div class="flex items-center justify-between pt-2">
                @if ($cancel)
                    <a href="{{ $cancel }}" class="text-sm text-slate-600 hover:text-slate-800 transition-colors">Annulla</a>
                @else
                    <span></span>
                @endif
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 cursor-pointer">
                    {{ $submit }}
                </button>
            </div>
        </form>
    </div>
</div>
