<?php

namespace App\Ticketing\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Thin wrapper over the Anthropic Messages API (plain HTTP, no SDK).
 */
class AnthropicClient
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function messages(array $payload, int $timeout, string $limiterKey, int $perMinute): array
    {
        $key = config('ticketing.llm.api_key');

        if (blank($key)) {
            throw new LlmUnavailableException('Chiave API Anthropic non configurata.');
        }

        if (RateLimiter::tooManyAttempts($limiterKey, $perMinute)) {
            throw new LlmUnavailableException('Limite di richieste al minuto raggiunto.');
        }
        RateLimiter::hit($limiterKey, 60);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => config('ticketing.llm.version'),
            ])
                ->acceptJson()
                ->timeout($timeout)
                ->retry(2, 500, throw: false)
                ->post(rtrim(config('ticketing.llm.base_url'), '/').'/v1/messages', $payload);
        } catch (\Throwable $exception) {
            throw new LlmUnavailableException('Anthropic non raggiungibile: '.$exception->getMessage(), previous: $exception);
        }

        if (! $response->successful()) {
            throw new LlmUnavailableException(trim("Anthropic ha risposto {$response->status()}. ".Str::limit((string) $response->json('error.message'), 300)));
        }

        $body = $response->json();

        Log::info('Ticketing: chiamata LLM', [
            'model' => $payload['model'] ?? null,
            'input_tokens' => data_get($body, 'usage.input_tokens'),
            'output_tokens' => data_get($body, 'usage.output_tokens'),
        ]);

        return is_array($body) ? $body : throw new LlmUnavailableException('Risposta Anthropic non valida.');
    }
}
