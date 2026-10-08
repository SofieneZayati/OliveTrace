<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LabResultAIAssistant
{
    public static function generateExplanation($acidity, $peroxideValue, $notes)
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return "AI Assistant is unavailable (Missing API Key). However, based on typical standards, an acidity of $acidity and peroxide value of $peroxideValue requires manual review.";
        }

        $prompt = "You are a lab-result explanation assistant for olive oil certification. 
The analysis results are:
- Acidity: {$acidity}%
- Peroxide Value: {$peroxideValue} meq O2/kg
- Lab notes: " . ($notes ?: 'None') . "

Please produce a plain-language explanation of what these results mean for the quality of the olive oil. 
Flag any suspicious or out-of-range values (e.g. Extra Virgin Olive Oil usually has acidity <= 0.8% and peroxide value <= 20). 
Keep it concise and professional.";

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Unable to parse AI response.";
            }

            Log::error('Gemini API Error: ' . $response->body());
            return "AI Assistant error: Unable to generate explanation.";
        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return "AI Assistant exception: " . $e->getMessage();
        }
    }
}
