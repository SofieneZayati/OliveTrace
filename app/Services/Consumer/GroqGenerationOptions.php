<?php

namespace App\Services\Consumer;

final class GroqGenerationOptions
{
    public static function forModel(int $answerTokens): array
    {
        if (str_starts_with((string) config('services.groq.model'), 'openai/gpt-oss-')) {
            return [
                'max_completion_tokens' => max(1536, $answerTokens * 4),
                'reasoning_effort' => 'low',
                'include_reasoning' => false,
            ];
        }

        return ['max_completion_tokens' => $answerTokens];
    }
}
