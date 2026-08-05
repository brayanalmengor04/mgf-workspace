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
    ],

];
