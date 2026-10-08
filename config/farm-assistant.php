<?php

return [
    'provider' => env('FARM_AI_PROVIDER', 'gemini'),
    'model' => env('FARM_AI_MODEL', env('FARM_AI_PROVIDER', 'gemini') === 'openai' ? 'gpt-4.1-mini' : 'gemini-3.1-flash-lite'),
    'gemini_key' => env('GEMINI_API_KEY'),
    'openai_key' => env('OPENAI_API_KEY'),
    'ollama_url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
    'timeout' => 30,
];
