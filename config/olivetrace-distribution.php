<?php

return [
    'trace_path' => env('OLIVETRACE_TRACE_PATH', '/trace'),
    'co2_emission_factors' => [
        'truck' => 0.10,
        'van' => 0.25,
        'rail' => 0.03,
        'ship' => 0.015,
        'air' => 0.60,
    ],
    'impact_ai_enabled' => env('OLIVETRACE_IMPACT_AI_ENABLED', false),
];
