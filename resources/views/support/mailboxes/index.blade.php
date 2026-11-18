@extends('layouts.support')

@section('title', 'Caselle email — ProgrammArti')
@section('page-title', 'Caselle email')

@section('content')
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-600">Le email ricevute in queste caselle diventano ticket; le risposte partono dalla casella in cui il cliente ha scritto.</p>
            <a href="{{ route('support.mailboxes.create') }}" class="btn btn-primary">Collega casella</a>
        </div>

        <x-table :items="$mailboxes" empty="Nessuna casella collegata: collega la prima per iniziare a ricevere ticket.">
            <x-slot:head>
                <th class="th">Casella</th>
                <th class="th">Tipo</th>
                <th class="th">Stato</th>
                <th class="th">Ultima sincronizzazione</th>
                <th class="th">Ticket</th>
                <th class="th text-right">Azioni</th>
            </x-slot:head>

            @foreach ($mailboxes as $mailbox)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="td">
                        <p class="font-medium text-slate-800">{{ $mailbox->name }}</p>
                        <p class="text-xs text-slate-600">{{ $mailbox->address }}</p>
                    </td>
                    <td class="td text-slate-700">
                        @if ($mailbox->isGmail())
                            Gmail{{ $mailbox->oauth_refresh_token ? ' (Google OAuth)' : '' }}
                        @else
                            IMAP/SMTP ({{ $mailbox->imap_host }})
                        @endif
                    </td>
                    <td class="td">
                        @if (! $mailbox->active)
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">Disattiva</span>
                        @elseif ($mailbox->isDisconnected())
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Disconnessa</span>
                        @else
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Attiva</span>
                        @endif
                    </td>
                    <td class="td text-slate-700">{{ $mailbox->last_synced_at?->diffForHumans() ?? 'mai' }}</td>
                    <td class="td text-slate-700">{{ $mailbox->tickets_count }}</td>
                    <td class="td text-right">
                        <a href="{{ route('support.mailboxes.edit', $mailbox) }}" class="link text-sm">Gestisci</a>
                    </td>
                </tr>
            @endforeach
        </x-table>
    </div>
@endsection
