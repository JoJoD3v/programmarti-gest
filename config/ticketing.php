<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Casella Gmail (IMAP in lettura, SMTP in invio, password per le app)
    |--------------------------------------------------------------------------
    |
    | Le credenziali vere stanno cifrate nel database (pagina "Casella collegata");
    | le variabili .env servono come valori iniziali e per i parametri tecnici.
    |
    */

    'mailbox' => [
        'driver' => env('TICKETING_MAILBOX_DRIVER', 'gmail_imap'),
        'address' => env('TICKETING_MAILBOX'),
        'imap_host' => env('TICKETING_IMAP_HOST', 'imap.gmail.com'),
        'imap_port' => (int) env('TICKETING_IMAP_PORT', 993),
        'smtp_host' => env('TICKETING_SMTP_HOST', 'smtp.gmail.com'),
        'smtp_port' => (int) env('TICKETING_SMTP_PORT', 465),
        'folder' => env('TICKETING_FOLDER', 'INBOX'),
        'timeout' => (int) env('TICKETING_TIMEOUT', 20),
        'initial_days' => (int) env('TICKETING_INITIAL_DAYS', 2),
        'batch_size' => (int) env('TICKETING_BATCH', 50),
        'max_attachment_kb' => (int) env('TICKETING_MAX_ATTACHMENT_KB', 10240),
        'daily_send_limit' => (int) env('TICKETING_DAILY_SEND_LIMIT', 450),
        'message_id_domain' => env('TICKETING_MESSAGE_ID_DOMAIN', 'gestionale.local'),
        'labels' => [
            'imported' => 'gestionale/importata',
            'discarded' => 'gestionale/scartata',
        ],
        'storage_disk' => env('TICKETING_DISK', 'local'),
        'storage_path' => 'support',
        'oauth' => [
            'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
            'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
            'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url' => 'https://oauth2.googleapis.com/token',
            'timeout' => 15,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Ciclo di vita dei ticket
    |--------------------------------------------------------------------------
    */

    'auto_close_days' => (int) env('TICKETING_AUTO_CLOSE_DAYS', 2),
    'reopen_days' => (int) env('TICKETING_REOPEN_DAYS', 7),
    'holidays' => [], // date Y-m-d da saltare nel conteggio dei giorni lavorativi
    'unclassified_assign_after_minutes' => 10,

    'rating' => [
        'scale' => (int) env('TICKETING_RATING_SCALE', 5), // 5 oppure 3
        'survey_delay_minutes' => (int) env('TICKETING_SURVEY_DELAY', 30),
        'survey_min_gap_days' => 7,
        'link_valid_days' => 14,
        'report_min_ratings' => (int) env('TICKETING_REPORT_MIN_RATINGS', 10),
        'low_score_threshold' => 2,
    ],

    'language' => env('TICKETING_LANGUAGE', 'it'),

    /*
    |--------------------------------------------------------------------------
    | LLM (Anthropic Messages API, chiamata HTTP diretta)
    |--------------------------------------------------------------------------
    */

    'llm' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
        'version' => '2023-06-01',
        'classifier_model' => env('TICKETING_CLASSIFIER_MODEL', 'claude-haiku-5-5'),
        'classifier_max_tokens' => 400,
        'timeout' => 20,
        'rate_per_minute' => 30,
        'body_chars' => 2000,
    ],

    'drafts' => [
        'model' => env('TICKETING_DRAFT_MODEL', 'claude-sonnet-5-5'),
        'max_tokens' => 800,
        'timeout' => 30,
        'context_messages' => 6,
        'max_context_chars' => 12000,
        'rate_per_minute' => 20,
        'prompt_version' => 'v1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ordini e-commerce (OrderProvider)
    |--------------------------------------------------------------------------
    */

    'orders' => [
        'driver' => env('TICKETING_ORDERS_DRIVER', 'woocommerce'),
        'number_source' => env('TICKETING_ORDER_NUMBER_SOURCE', 'id'), // id | meta
        'number_meta_key' => env('TICKETING_ORDER_NUMBER_META', '_order_number'),
        'tracking_meta_keys' => ['_tracking_number', 'tracking_number', '_wc_shipment_tracking_items'],
        'carrier_meta_keys' => ['_tracking_provider', 'tracking_provider'],
        'number_min_digits' => 3,
        'number_max_digits' => 10,
        'recent_days' => 90,
        'timeout' => 10,
        'refresh_seconds' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy
    |--------------------------------------------------------------------------
    |
    | Mesi dopo i quali corpi e allegati dei ticket chiusi vengono anonimizzati.
    | null = disattivato (da decidere con il cliente).
    |
    */

    'retention_months' => env('TICKETING_RETENTION_MONTHS') !== null ? (int) env('TICKETING_RETENTION_MONTHS') : null,

    'alerts' => [
        'sync_stale_minutes' => 15,
        'unclassified_minutes' => 60,
        'unassigned_minutes' => 60,
        'unanswered_hours' => 24,
        'send_limit_warning_percent' => 80,
    ],

];
