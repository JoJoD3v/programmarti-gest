<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');

        if (!$this->apiKey) {
            throw new \Exception('OpenAI API key not configured');
        }
    }

    /**
     * Enhance work item descriptions using ChatGPT
     */
    public function enhanceWorkItems(string $mainDescription, array $workItems): array
    {
        try {
            Log::info('OpenAI Enhancement Started', [
                'main_description' => $mainDescription,
                'work_items_count' => count($workItems),
                'api_key_length' => strlen($this->apiKey)
            ]);

            $prompt = $this->buildPrompt($mainDescription, $workItems);

            // Configure HTTP client with SSL options for Windows compatibility
            $httpClient = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'User-Agent' => 'ProgrammArti-Gestionale/1.0'
            ])->timeout(30);

            // Add SSL verification options for development environments
            if (app()->environment('local', 'development')) {
                $httpClient = $httpClient->withOptions([
                    'verify' => false, // Disable SSL verification for local development
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ]);
            }

            $requestData = [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Sei un esperto consulente IT che aiuta a creare preventivi dettagliati. Fornisci spiegazioni tecniche chiare e professionali in italiano per ogni voce di lavoro.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ];

            Log::info('Making OpenAI API Request', [
                'url' => $this->baseUrl . '/chat/completions',
                'model' => $requestData['model'],
                'prompt_length' => strlen($prompt)
            ]);

            $response = $httpClient->post($this->baseUrl . '/chat/completions', $requestData);

            Log::info('OpenAI API Response', [
                'status' => $response->status(),
                'successful' => $response->successful(),
                'response_size' => strlen($response->body())
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $enhancedContent = $data['choices'][0]['message']['content'] ?? '';

                Log::info('OpenAI Enhancement Successful', [
                    'content_length' => strlen($enhancedContent),
                    'usage' => $data['usage'] ?? null
                ]);

                return $this->parseEnhancedDescriptions($enhancedContent, $workItems);
            } else {
                Log::error('OpenAI API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers' => $response->headers()
                ]);
                return $this->getFallbackDescriptions($workItems);
            }
        } catch (\Exception $e) {
            Log::error('OpenAI Service Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return $this->getFallbackDescriptions($workItems);
        }
    }

    /**
     * Build the prompt for ChatGPT
     */
    private function buildPrompt(string $mainDescription, array $workItems): string
    {
        $prompt = "Progetto: {$mainDescription}\n\n";
        $prompt .= "Voci di lavoro da dettagliare:\n\n";

        foreach ($workItems as $index => $item) {
            $prompt .= ($index + 1) . ". {$item['description']}\n";
        }

        $prompt .= "\nPer favore, fornisci una spiegazione dettagliata e professionale per ogni voce di lavoro. ";
        $prompt .= "NON ripetere il titolo o il costo della voce. ";
        $prompt .= "Inizia direttamente con la spiegazione dettagliata. ";
        $prompt .= "Includi aspetti tecnici, benefici per il cliente e metodologie utilizzate. ";
        $prompt .= "Formatta la risposta come:\n\n";
        $prompt .= "VOCE 1:\n[Spiegazione dettagliata senza ripetere il titolo]\n\n";
        $prompt .= "VOCE 2:\n[Spiegazione dettagliata senza ripetere il titolo]\n\n";
        $prompt .= "E così via per tutte le voci.";

        return $prompt;
    }

    /**
     * Parse the enhanced descriptions from ChatGPT response
     */
    private function parseEnhancedDescriptions(string $content, array $workItems): array
    {
        $enhanced = [];
        $sections = preg_split('/VOCE \d+:/i', $content);

        // Remove the first empty section
        array_shift($sections);

        foreach ($workItems as $index => $item) {
            $enhanced[] = [
                'description' => $item['description'],
                'cost' => $item['cost'],
                'ai_enhanced_description' => isset($sections[$index])
                    ? trim($sections[$index])
                    : $this->getFallbackDescription($item['description'])
            ];
        }

        return $enhanced;
    }

    /**
     * Get fallback descriptions when AI fails
     */
    private function getFallbackDescriptions(array $workItems): array
    {
        Log::warning('Using fallback descriptions - AI enhancement not available');

        return array_map(function ($item) {
            return [
                'description' => $item['description'],
                'cost' => $item['cost'],
                'ai_enhanced_description' => $this->getFallbackDescription($item['description'])
            ];
        }, $workItems);
    }

    /**
     * Generate a professional fallback description based on keywords
     */
    private function getFallbackDescription(string $description): string
    {
        $lowerDescription = strtolower($description);

        // Template-based descriptions for common service types
        if (str_contains($lowerDescription, 'sviluppo') || str_contains($lowerDescription, 'development')) {
            return "Include analisi dei requisiti, progettazione dell'architettura, implementazione con tecnologie moderne, testing completo e documentazione tecnica. Il servizio garantisce codice di alta qualità, scalabilità e manutenibilità nel tempo.";
        }

        if (str_contains($lowerDescription, 'design') || str_contains($lowerDescription, 'grafica')) {
            return "Comprende studio dell'user experience, creazione di mockup e prototipi, definizione della brand identity, ottimizzazione per tutti i dispositivi e consegna di tutti i file sorgente in formati standard.";
        }

        if (str_contains($lowerDescription, 'integrazione') || str_contains($lowerDescription, 'api')) {
            return "Include analisi delle API esistenti, sviluppo di connettori personalizzati, testing di compatibilità, gestione degli errori e monitoraggio delle performance per garantire un funzionamento ottimale.";
        }

        if (str_contains($lowerDescription, 'consulenza') || str_contains($lowerDescription, 'analisi')) {
            return "Comprende analisi approfondita della situazione attuale, identificazione delle migliori soluzioni, pianificazione strategica e supporto nell'implementazione con metodologie comprovate.";
        }

        if (str_contains($lowerDescription, 'manutenzione') || str_contains($lowerDescription, 'supporto')) {
            return "Include monitoraggio continuo, aggiornamenti di sicurezza, ottimizzazioni delle performance, backup automatici e supporto tecnico dedicato per garantire il massimo uptime.";
        }

        // Default professional description
        return "Questo servizio viene erogato seguendo le migliori pratiche del settore, utilizzando tecnologie all'avanguardia e garantendo risultati di alta qualità con supporto completo e documentazione dettagliata.";
    }
}
