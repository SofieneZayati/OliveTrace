<?php

return [
    'provider' => env('FARM_AI_PROVIDER', 'openai'),
    'model' => env('FARM_AI_MODEL', 'gpt-4.1-mini'),
    'openai_key' => env('OPENAI_API_KEY'),
    'ollama_url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
    'timeout' => 30,
];
