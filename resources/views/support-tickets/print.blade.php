@extends('layouts.print')

@use('App\Ticketing\Support\QuotedText')
@use('App\Ticketing\Enums\MessageType')

@section('title', "Ticket {$ticket->public_id}")

@section('content')
    @include('print.company-header', ['compact' => true])

    <div class="flex items-start justify-between gap-4 mb-3">
        <div>
            <h1 class="text-base font-bold">Ticket {{ $ticket->public_id }}</h1>
            <p class="text-sm">{{ $ticket->subject ?: '(senza oggetto)' }}</p>
        </div>
        <p class="text-right">Stampato il {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table class="w-full border border-slate-300 mb-4">
        <tr>
            <th class="px-2 py-1 border border-slate-300 bg-slate-100 text-left w-32">Cliente</th>
            <td class="px-2 py-1 border border-slate-300">{{ $ticket->customer_name ?: '—' }} &lt;{{ $ticket->customer_email }}&gt;</td>
            <th class="px-2 py-1 border border-slate-300 bg-slate-100 text-left w-32">Stato</th>
            <td class="px-2 py-1 border border-slate-300">{{ $ticket->status->label() }}@if ($ticket->close_reason) ({{ $ticket->close_reason->label() }})@endif</td>
        </tr>
        <tr>
            <th class="px-2 py-1 border border-slate-300 bg-slate-100 text-left">Tipologia</th>
            <td class="px-2 py-1 border border-slate-300">{{ $ticket->type?->name ?? 'Da classificare' }}</td>
            <th class="px-2 py-1 border border-slate-300 bg-slate-100 text-left">Cliente</th>
            <td class="px-2 py-1 border border-slate-300">{{ $ticket->client?->full_name ?? '—' }}</td>
        </tr>
        <tr>
            <th class="px-2 py-1 border border-slate-300 bg-slate-100 text-left">Aperto il</th>
            <td class="px-2 py-1 border border-slate-300">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
            <th class="px-2 py-1 border border-slate-300 bg-slate-100 text-left">Assegnato a</th>
            <td class="px-2 py-1 border border-slate-300">{{ $ticket->assignee?->name ?? '—' }}</td>
        </tr>
        @if ($ticket->closed_at)
            <tr>
                <th class="px-2 py-1 border border-slate-300 bg-slate-100 text-left">Chiuso il</th>
                <td class="px-2 py-1 border border-slate-300" colspan="3">{{ $ticket->closed_at->format('d/m/Y H:i') }}@if ($ticket->closedByUser) da {{ $ticket->closedByUser->name }}@endif</td>
            </tr>
        @endif
    </table>

    <h2 class="text-sm font-bold uppercase mb-2">Conversazione</h2>
    @foreach ($messages as $message)
        <article class="border border-slate-300 mb-2 break-inside-avoid">
            <header class="flex justify-between gap-2 px-2 py-1 bg-slate-100 border-b border-slate-300">
                <span class="font-semibold">
                    @if ($message->isInbound())
                        {{ $message->from_name ?: $message->from_email }} &lt;{{ $message->from_email }}&gt;
                    @else
                        {{ $message->sender?->full_name ?? 'ProgrammArti' }} → cliente
                    @endif
                </span>
                <span>{{ $message->happenedAt()->format('d/m/Y H:i') }}</span>
            </header>
            {{-- ponytail: HTML-only emails print as plain text, the layout of the original email is lost --}}
            <div class="px-2 py-1.5 whitespace-pre-line break-words">{{ QuotedText::visible((string) ($message->body_text ?: html_entity_decode(strip_tags((string) $message->body_html_sanitized)))) }}</div>
            @if ($message->attachments->where('inline', false)->isNotEmpty())
                <p class="px-2 pb-1.5 text-[11px]">Allegati: {{ $message->attachments->where('inline', false)->pluck('filename')->join(', ') }}</p>
            @endif
        </article>
    @endforeach
@endsection
