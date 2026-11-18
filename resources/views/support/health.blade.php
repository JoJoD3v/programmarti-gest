@extends('layouts.support')

@section('title', 'Stato sistema — Ticket email')
@section('page-title', 'Stato del sistema assistenza')

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <section class="card">
            <h2 class="card-title">Controlli</h2>
            <ul class="divide-y divide-slate-50 text-sm">
                @foreach ($checks as $check)
                    <li class="px-5 py-3 flex items-start justify-between gap-4">
                        <span>{{ $check['label'] }}</span>
                        <span class="text-right {{ $check['ok'] ? 'text-green-700' : 'text-red-700 font-medium' }}">{{ $check['ok'] ? '✔' : '✖' }} {{ $check['detail'] }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="card">
            <h2 class="card-title">Ultimi job falliti</h2>
            @if ($failedJobs->isEmpty())
                <p class="p-5 text-sm text-slate-600">Nessun job fallito.</p>
            @else
                <ul class="divide-y divide-slate-50 text-xs">
                    @foreach ($failedJobs as $job)
                        <li class="px-5 py-2"><span class="font-medium">#{{ $job->id }}</span> · {{ $job->failed_at }} · {{ \Illuminate\Support\Str::limit(strtok($job->exception, "\n"), 160) }}</li>
                    @endforeach
                </ul>
                <p class="px-5 pb-4 text-xs text-slate-600">Rilancio: <code>php artisan queue:retry all</code> — vedi docs/ticketing/RUNBOOK.md</p>
            @endif
        </section>
    </div>
@endsection
