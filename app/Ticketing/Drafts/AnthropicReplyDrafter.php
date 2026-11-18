<?php

namespace App\Ticketing\Drafts;

use App\Models\SupportTicket;
use App\Models\User;
use App\Ticketing\Contracts\ReplyDrafter;
use App\Ticketing\DTO\ReplyDraft;
use App\Ticketing\Support\AnthropicClient;
use App\Ticketing\Support\LlmUnavailableException;

class AnthropicReplyDrafter implements ReplyDrafter
{
    public function __construct(
        private readonly DraftContextBuilder $context,
        private readonly DraftsFeature $feature,
        private readonly AnthropicClient $client,
    ) {}

    public function draft(SupportTicket $ticket, User $author, ?string $instruction = null): ReplyDraft
    {
        if (! $this->feature->enabled()) {
            throw new LlmUnavailableException('Le bozze AI sono disattivate.');
        }

        $model = (string) config('ticketing.drafts.model');
        $user = $this->context->user($ticket, $author, $instruction);

        $response = $this->client->messages([
            'model' => $model,
            'max_tokens' => (int) config('ticketing.drafts.max_tokens'),
            'system' => $this->context->system(),
            'messages' => [['role' => 'user', 'content' => $user['text']]],
        ], (int) config('ticketing.drafts.timeout'), 'ticketing-llm-drafts', (int) config('ticketing.drafts.rate_per_minute'));

        $text = collect((array) ($response['content'] ?? []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");

        if (trim($text) === '') {
            throw new LlmUnavailableException('Il modello non ha prodotto una bozza.');
        }

        return new ReplyDraft(
            trim($text),
            $model,
            (string) config('ticketing.drafts.prompt_version'),
            (int) data_get($response, 'usage.input_tokens', 0),
            (int) data_get($response, 'usage.output_tokens', 0),
        );
    }
}
