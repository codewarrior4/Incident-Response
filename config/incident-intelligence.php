<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Enhancement
    |--------------------------------------------------------------------------
    |
    | Enable or disable AI-powered analysis using Ollama.
    |
    */
    'ai_enabled' => env('INCIDENT_AI_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Ollama Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Ollama AI service integration.
    |
    */
    'ollama_url' => env('INCIDENT_OLLAMA_URL', 'http://localhost:11434'),
    'ollama_model' => env('INCIDENT_OLLAMA_MODEL', 'llama3'),
    'ollama_timeout' => env('INCIDENT_OLLAMA_TIMEOUT', 25),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Queue connection and retry settings for incident processing.
    |
    */
    'queue_connection' => env('INCIDENT_QUEUE_CONNECTION', 'redis'),
    'analysis_retry_attempts' => env('INCIDENT_ANALYSIS_RETRY_ATTEMPTS', 3),
];
