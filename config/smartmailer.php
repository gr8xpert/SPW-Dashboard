<?php

return [
    'name'             => env('APP_NAME', 'Smart Property Widget'),
    'tracking_domain'  => env('TRACKING_DOMAIN', env('APP_URL')),
    'n8n_webhook_base' => env('N8N_WEBHOOK_BASE_URL'),
    'n8n_api_key'      => env('N8N_API_KEY'),
    'anthropic_key'    => env('ANTHROPIC_API_KEY'),
    'openrouter_key'   => env('OPENROUTER_API_KEY'),
    'unlayer_project'  => env('UNLAYER_PROJECT_ID'),
    'smtp_encryption_key' => env('SMTP_ENCRYPTION_KEY', env('APP_KEY')),
    'unsplash_key'     => env('UNSPLASH_ACCESS_KEY'),
    'internal_api_key' => env('INTERNAL_API_KEY'),
    'widget_proxy_url' => env('WIDGET_PROXY_URL'),

    'plans' => [
        'free_trial_days' => 14,
    ],

    'sending' => [
        'default_batch_size'    => 50,
        'batch_delay_seconds'   => 30,
        'min_delay_between'     => 2,
        'max_delay_between'     => 8,
        'max_retry_attempts'    => 3,
        'bounce_pause_threshold'    => 0.03,  // 3%
        'complaint_suspend_threshold' => 0.001, // 0.1%
    ],

    'ai' => [
        'model'      => 'claude-sonnet-4-6',
        'max_tokens' => 4096,
    ],

    'widget' => [
        'grace_period_days' => 7,
        'grace_reminder_day' => 3,  // send reminder when 3 days into grace
        'cache_ttl_seconds' => 300, // 5-minute cache for subscription checks
    ],

    'security' => [
        'login_max_attempts'    => 5,
        'login_lockout_minutes' => 15,
        'session_timeout'       => 30,
        'max_concurrent_sessions' => 3,
    ],

    'paddle' => [
        'vendor_id'      => env('PADDLE_VENDOR_ID'),
        'api_key'        => env('PADDLE_API_KEY'),
        'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),
        'sandbox'        => env('PADDLE_SANDBOX', true),
    ],

    'credits' => [
        'default_rate'      => 50.00,   // default hourly rate
        'low_balance_hours' => 2,       // warn when balance drops below this
        'packs' => [
            ['hours' => 5,  'price' => 250],
            ['hours' => 10, 'price' => 450],
            ['hours' => 20, 'price' => 800],
        ],
    ],

    'tickets' => [
        'auto_close_days' => 7,     // close resolved tickets after 7 days
        'reopen_window_days' => 7,  // clients can reopen within 7 days
        'sla' => [
            'urgent' => 4,   // hours for first response
            'high'   => 8,
            'medium' => 24,
            'low'    => 48,
        ],
        'max_attachment_size' => 10 * 1024 * 1024,  // 10MB
        'max_attachments_per_message' => 30 * 1024 * 1024, // 30MB total
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'doc', 'docx', 'txt'],
    ],
];
