@extends('layouts.support')

@section('title', 'Cestino Gmail — ProgrammArti')
@section('page-title', 'Cestino Gmail')

@section('content')
    <p class="text-sm text-slate-600 mb-4">
        Le ultime email nel cestino della casella Gmail, raggruppate per conversazione. Gmail le elimina definitivamente dopo 30 giorni; «Ripristina» riporta l'intera conversazione in Posta in arrivo. Le caselle IMAP dell'hosting non hanno questa funzione.
    </p>

    @if ($mailboxes->count() > 1)
        <form method="GET" class="mb-4 flex items-center gap-2 text-sm">
            <label for="mailbox" class="text-slate-600">Casella</label>
            <select id="mailbox" name="mailbox" class="filter-input" onchange="this.form.submit()">
                @foreach ($mailboxes as $option)
                    <option value="{{ $option->id }}" @selected($option->id === $mailbox?->id)>{{ $option->address }}</option>
                @endforeach
            </select>
        </form>
    @endif

    @if ($error)
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ $error }}</div>
    @endif

    @error('restore')
        <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">{{ $message }}</div>
    @enderror

    <x-table :items="$threads" title="Conversazioni nel cestino" empty="Il cestino Gmail è vuoto.">
        <x-slot:head>
            <th class="th">Data</th>
            <th class="th">Mittente</th>
            <th class="th">Oggetto</th>
            <th class="th">Ticket</th>
            <th class="th text-right">Azioni</th>
        </x-slot:head>

        @foreach ($threads as $threadId => $thread)
            @php($email = $thread['latest'])
            <tr class="hover:bg-slate-50 transition-colors">
                <td class="td whitespace-nowrap">{{ $email->date?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="td">
                    <div class="text-slate-800">{{ $email->fromName ?? $email->fromAddress ?? '—' }}</div>
                    @if ($email->fromName && $email->fromAddress)<div class="text-xs text-slate-500">{{ $email->fromAddress }}</div>@endif
                </td>
                <td class="td">
                    {{ $email->subject !== '' ? $email->subject : '(senza oggetto)' }}
                    @if ($thread['count'] > 1)<span class="text-xs text-slate-500">· {{ $thread['count'] }} email</span>@endif
                </td>
                <td class="td">
                    @if ($ticket = $tickets->get($threadId))
                        <a href="{{ route('support-tickets.show', $ticket) }}" class="link font-medium">{{ $ticket->public_id }}</a>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </td>
                <td class="td text-right whitespace-nowrap">
                    <x-action-button :action="route('support.gmail-trash.restore', [$mailbox, $threadId])" variant="btn-primary"
                                     confirm="Riportare questa conversazione in Posta in arrivo?">Ripristina</x-action-button>
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection
