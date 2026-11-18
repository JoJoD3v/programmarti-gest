<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Stampa') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
    @stack('head')
    <style>
        @page { size: A4; margin: 10mm; }
        @media print {
            .no-print { display: none !important; }
            .page-break { break-before: page; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 text-[12px] leading-snug">
    <div class="no-print sticky top-0 bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between">
        <x-back-link :href="url()->previous()">Indietro</x-back-link>
        <button type="button" onclick="window.print()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-4 py-2 rounded-lg cursor-pointer">
            Stampa
        </button>
    </div>

    <main class="max-w-[210mm] mx-auto bg-white p-[10mm] my-6 print:my-0 print:p-0 shadow print:shadow-none">
        @yield('content')
    </main>
</body>
</html>
