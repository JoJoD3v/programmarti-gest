<?php

namespace App\Ticketing\Support;

use App\Models\SupportTicket;
use App\Models\User;

/**
 * Catalogue of the {variables} usable in saved replies, and their substitution.
 * A variable without a value becomes a [[DA COMPLETARE: …]] marker, which blocks sending until edited.
 */
class SavedReplyVariables
{
    /**
     * @return array<string, string> variable => description
     */
    public static function catalog(): array
    {
        return [
            '{cliente_nome}' => 'Nome del cliente',
            '{progetto}' => 'Nome del progetto in corso del cliente (se ne ha uno solo)',
            '{numero_ticket}' => 'Numero del ticket',
            '{dipendente_nome}' => 'Nome di chi risponde',
        ];
    }

    public function replace(string $body, SupportTicket $ticket, User $agent): string
    {
        $values = [
            '{cliente_nome}' => $ticket->customerFirstName(),
            '{progetto}' => $this->activeProjectName($ticket),
            '{numero_ticket}' => $ticket->public_id,
            '{dipendente_nome}' => $agent->first_name,
        ];

        foreach ($values as $variable => $value) {
            $label = trim($variable, '{}');
            $body = str_replace($variable, filled($value) ? (string) $value : "[[DA COMPLETARE: {$label}]]", $body);
        }

        return $body;
    }

    /**
     * Reverse substitution for "Salva come risposta": recognizable values become variables again.
     */
    public function abstract(string $text, SupportTicket $ticket): string
    {
        $pairs = array_filter([
            '{progetto}' => $this->activeProjectName($ticket),
            '{cliente_nome}' => $ticket->customerFirstName(),
            '{numero_ticket}' => $ticket->public_id,
        ], fn ($value) => filled($value));

        foreach ($pairs as $variable => $value) {
            $text = str_replace((string) $value, $variable, $text);
        }

        return $text;
    }

    /**
     * The project the ticket is most likely about: only when the linked client has exactly one project in progress.
     */
    private function activeProjectName(SupportTicket $ticket): ?string
    {
        $projects = $ticket->client?->projects()->where('status', 'in_progress')->pluck('name');

        return $projects !== null && $projects->count() === 1 ? $projects->first() : null;
    }
}
