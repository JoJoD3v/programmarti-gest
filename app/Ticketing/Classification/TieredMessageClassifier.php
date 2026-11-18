<?php

namespace App\Ticketing\Classification;

use App\Models\SupportSenderRule;
use App\Ticketing\Actions\ProcessInboundMessage;
use App\Ticketing\Contracts\MessageClassifier;
use App\Ticketing\DTO\Classification;
use App\Ticketing\DTO\InboundEmail;
use App\Ticketing\Support\LlmUnavailableException;
use App\Ticketing\Support\TicketingSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Sender rule → mass-mail / known-client hints → LLM.
 * The hints only enrich the LLM request; if the LLM is down the ticket stays unclassified.
 */
class TieredMessageClassifier implements MessageClassifier
{
    public function __construct(
        private readonly AnthropicClassifier $llm,
        private readonly TicketingSettings $settings,
    ) {}

    public function classify(InboundEmail $email, Collection $ticketTypes): Classification
    {
        $rule = SupportSenderRule::query()->active()->with('type')->get()
            ->first(fn (SupportSenderRule $rule) => $rule->type?->active && $rule->matches($email->fromEmail));

        if ($rule !== null) {
            return new Classification($rule->type->slug, 1.0, 'rule', 'it');
        }

        $hints = [];
        if ($this->isMassMail($email)) {
            $hints[] = 'Intestazioni da posta massiva (List-Id/List-Unsubscribe/Precedence: bulk): probabile newsletter o notifica automatica.';
        }
        if (ProcessInboundMessage::clientIdFor($email->fromEmail) !== null) {
            $hints[] = 'Il mittente è un cliente dell\'agenzia.';
        }

        if (! $this->settings->bool('llm_classifier_enabled')) {
            return new Classification(null, 0.0, 'fallback');
        }

        try {
            $result = $this->llm->classifyWithHints($email, $ticketTypes, $hints);
        } catch (LlmUnavailableException $exception) {
            Log::warning('Ticketing: classificazione LLM non disponibile', ['error' => $exception->getMessage()]);

            return new Classification(null, 0.0, 'fallback');
        }

        return $result;
    }

    private function isMassMail(InboundEmail $email): bool
    {
        return $email->header('list-id') !== null
            || $email->header('list-unsubscribe') !== null
            || in_array(strtolower((string) $email->header('precedence')), ['bulk', 'list', 'junk'], true);
    }
}
