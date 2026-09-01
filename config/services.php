<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'brevo' => [
        'key' => env('BREVO_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.1-flash-lite'),
        'thinking_level' => env('GEMINI_THINKING_LEVEL', 'minimal'),
        'translate_fallback' => env('GEMINI_TRANSLATE_FALLBACK', true),
        'rate_limits' => [
            // Ajusta según tu panel de AI Studio (Rate limits)
            'rpm' => (int) env('GEMINI_RATE_LIMIT_RPM', 15),
            'tpm' => (int) env('GEMINI_RATE_LIMIT_TPM', 250_000),
            'rpd' => (int) env('GEMINI_RATE_LIMIT_RPD', 1_500),
        ],
        'chat' => [
            'max_conversations' => (int) env('GEMINI_CHAT_MAX_CONVERSATIONS', 5),
            'max_api_messages' => (int) env('GEMINI_CHAT_MAX_API_MESSAGES', 8),
            'summarize_after_messages' => (int) env('GEMINI_CHAT_SUMMARIZE_AFTER', 12),
            'context_cache_ttl' => (int) env('GEMINI_CHAT_CONTEXT_CACHE_TTL', 300),
            'response_cache_ttl' => (int) env('GEMINI_CHAT_RESPONSE_CACHE_TTL', 3600),
        ],
        'vision_model' => env('GEMINI_VISION_MODEL', 'gemini-3.1-flash-lite'),
        'vision_model_fallbacks' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('GEMINI_VISION_MODEL_FALLBACKS', 'gemini-3.6-flash'))
        ))),
    ],

    'budget_scan' => [
        'max_mb' => (int) env('BUDGET_SCAN_MAX_MB', 8),
        'rate_limit_per_hour' => (int) env('BUDGET_SCAN_RATE_LIMIT_PER_HOUR', 3),
        'delete_image_after' => env('BUDGET_SCAN_DELETE_IMAGE_AFTER', true),
        'optimize_max_dimension' => (int) env('BUDGET_SCAN_OPTIMIZE_MAX_DIMENSION', 1600),
        'optimize_jpeg_quality' => (int) env('BUDGET_SCAN_OPTIMIZE_JPEG_QUALITY', 82),
    ],

];
