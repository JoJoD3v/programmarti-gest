<?php

namespace App\Ticketing\Actions;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Ticketing\DTO\InboundEmail;
use App\Ticketing\DTO\MatchResult;
use Illuminate\Support\Collection;

/**
 * Decides which ticket an incoming email belongs to. Golden rule: a different sender never joins someone
 * else's ticket: it opens a new one flagged as "possibly related".
 */
class ThreadMatcher
{
    public function match(InboundEmail $email): MatchResult
    {
        $sender = mb_strtolower($email->fromEmail);

        foreach ($this->candidates($email) as [$criterion, $ticket]) {
            if ($ticket === null) {
                continue;
            }

            return mb_strtolower($ticket->customer_email) === $sender
                ? new MatchResult($ticket, $criterion)
                : new MatchResult(null, $criterion, $ticket);
        }

        return new MatchResult(null, 'none');
    }

    /**
     * Lazily evaluated, in priority order.
     *
     * @return \Generator<int, array{0: string, 1: ?SupportTicket}>
     */
    private function candidates(InboundEmail $email): \Generator
    {
        yield ['gmail_thread', $this->byGmailThread($email)];
        yield ['headers', $this->byHeaders($email)];
        yield ['subject_token', $this->bySubjectToken($email)];
    }

    private function byGmailThread(InboundEmail $email): ?SupportTicket
    {
        if ($email->gmailThreadId === null) {
            return null;
        }

        return $this->preferSameSender(
            SupportTicket::query()->where('gmail_thread_id', $email->gmailThreadId)->latest('id')->get(),
            $email,
        );
    }

    private function byHeaders(InboundEmail $email): ?SupportTicket
    {
        $ids = array_values(array_filter([$email->inReplyTo, ...array_reverse($email->references)]));

        if ($ids === []) {
            return null;
        }

        $ticketIds = SupportTicketMessage::query()->whereIn('message_id_header', $ids)->pluck('support_ticket_id')->unique();

        return $this->preferSameSender(SupportTicket::query()->whereIn('id', $ticketIds)->latest('id')->get(), $email);
    }

    private function bySubjectToken(InboundEmail $email): ?SupportTicket
    {
        if (preg_match('/\[(T-\d+)\]/', $email->subject, $match) !== 1) {
            return null;
        }

        return SupportTicket::query()->where('public_id', $match[1])->first();
    }

    /**
     * Several tickets can share a thread; keep the one of the same sender when there is one.
     *
     * @param  Collection<int, SupportTicket>  $tickets
     */
    private function preferSameSender($tickets, InboundEmail $email): ?SupportTicket
    {
        return $tickets->first(fn (SupportTicket $ticket) => mb_strtolower($ticket->customer_email) === mb_strtolower($email->fromEmail))
            ?? $tickets->first();
    }
}
