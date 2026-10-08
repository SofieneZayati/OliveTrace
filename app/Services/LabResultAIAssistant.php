<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LabResultAIAssistant
{
    public static function generateExplanation($acidity, $peroxideValue, $notes)
    {
        $apiKey = config('services.lab_ai.key');
        if (! $apiKey) {
            return 'AI Assistant is unavailable. Configure the Gemini API key to request an explanation.';
        }

        $prompt = "You are a lab-result explanation assistant for olive oil certification. 
The analysis results are:
- Acidity: {$acidity}%
- Peroxide Value: {$peroxideValue} meq O2/kg
- Lab notes: ".($notes ?: 'None').'

Please produce a plain-language explanation of what these results mean for the quality of the olive oil. 
Flag any suspicious or out-of-range values (e.g. Extra Virgin Olive Oil usually has acidity <= 0.8% and peroxide value <= 20). 
Keep it concise and professional.';

        try {
            $response = Http::withHeaders(['x-goog-api-key' => $apiKey])->acceptJson()->connectTimeout(5)->timeout(30)
                ->post('https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode(config('services.lab_ai.model')).':generateContent', [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['maxOutputTokens' => 2048, 'temperature' => 0.2],
                ]);

            if ($response->status() === 429) {
                return 'AI Assistant usage limit reached. Please try again later.';
            }

            if ($response->successful()) {
                if ($response->json('candidates.0.finishReason') !== 'STOP' || $response->json('promptFeedback.blockReason')) {
                    return 'Unable to parse AI response.';
                }

                $text = '';
                foreach ($response->json('candidates.0.content.parts', []) as $part) {
                    if (! ($part['thought'] ?? false) && is_string($part['text'] ?? null)) {
                        $text .= $part['text'];
                    }
                }

                return trim($text) !== '' ? $text : 'Unable to parse AI response.';
            }

            return 'AI Assistant error: Unable to generate explanation.';
        } catch (\Exception $e) {
            return 'AI Assistant is temporarily unavailable. Please try again later.';
        }
    }
}
