<?php

namespace App\Services\Consumer;

use App\Models\Consumer\AdminSuggestedResponse;
use App\Models\Consumer\Feedback;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use JsonException;
use RuntimeException;

class AdminSuggestedResponseService
{
    private const TONES = ['professional', 'empathetic', 'formal', 'friendly'];

    private const SYSTEM_PROMPT = 'You are a customer service assistant for OliveTrace, a Tunisian olive oil traceability platform.
Your task is to generate appropriate admin responses to consumer reviews/feedback.
The response should address the consumer\'s concerns, acknowledge their feedback, and maintain the brand\'s professional image.
Never make promises you cannot keep. Never admit legal liability. Always be courteous and helpful.
Match the requested tone exactly. Return concise responses suitable for a public platform.';

    private const USER_PROMPT_TEMPLATE = 'Consumer feedback to respond to:
Rating: {rating}/5
Comment: "{comment}"
AI Analysis: Category={category}, Sentiment={sentiment}, Priority={priority}

Generate a response in {tone} tone. Maximum 150 words.';

    public function generate(Feedback $feedback, string $tone = 'professional'): array
    {
        if (! in_array($tone, self::TONES, true)) {
            $tone = 'professional';
        }

        $provider = config('admin-ai.provider', 'openai');
        $model = config('admin-ai.model', 'gpt-4o-mini');
        $key = config('admin-ai.openai_key');

        if (! in_array($provider, ['openai', 'ollama'], true) || $provider === 'openai' && ! $key) {
            return $this->fallback($feedback, $tone);
        }

        $prompt = str_replace(
            ['{rating}', '{comment}', '{category}', '{sentiment}', '{priority}', '{tone}'],
            [
                $feedback->rating,
                $feedback->comment ?? 'No comment provided',
                $feedback->ai_category?->label() ?? 'Not categorized',
                $feedback->ai_sentiment?->label() ?? 'Neutral',
                $feedback->ai_priority?->label() ?? 'Normal',
                $tone,
            ],
            self::USER_PROMPT_TEMPLATE
        );

        try {
            $response = $this->callProvider($provider, $model, $key, $prompt);

            if (! is_string($response) || strlen($response) > 500) {
                throw new RuntimeException('Invalid AI response.');
            }

            $cleaned = trim($response);

            $validated = Validator::make(['response' => $cleaned], [
                'response' => ['required', 'string', 'max:500'],
            ])->validate();

            $suggestion = AdminSuggestedResponse::create([
                'feedback_id' => $feedback->id,
                'suggested_response' => $validated['response'],
                'tone' => $tone,
                'confidence' => 0.85,
            ]);

            return [
                'success' => true,
                'suggestion' => $suggestion,
            ];
        } catch (ConnectionException|RequestException|JsonException|RuntimeException) {
            return $this->fallback($feedback, $tone);
        }
    }

    public function generateMultipleTones(Feedback $feedback): array
    {
        $results = [];
        foreach (self::TONES as $tone) {
            $results[$tone] = $this->generate($feedback, $tone);
        }

        return $results;
    }

    private function callProvider(string $provider, string $model, ?string $key, string $prompt): string
    {
        if ($provider === 'openai') {
            $response = Http::withToken($key)->acceptJson()
                ->connectTimeout(10)->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'max_tokens' => 300,
                    'temperature' => 0.7,
                ])->throw();

            if (! isset($response['choices'][0]['message']['content'])) {
                throw new RuntimeException('Invalid OpenAI response format.');
            }

            return trim($response['choices'][0]['message']['content']);
        }

        // Ollama
        $response = Http::acceptJson()->connectTimeout(10)->timeout(30)
            ->post(rtrim(config('admin-ai.ollama_url', 'http://127.0.0.1:11434'), '/').'/api/chat', [
                'model' => $model,
                'stream' => false,
                'messages' => [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ])->throw();

        if (! isset($response['message']['content'])) {
            throw new RuntimeException('Invalid Ollama response format.');
        }

        return trim($response['message']['content']);
    }

    private function fallback(Feedback $feedback, string $tone): array
    {
        $templates = [
            'professional' => 'Thank you for your feedback. We appreciate you taking the time to share your experience with our product. Your input helps us improve our olive oil quality and service.',
            'empathetic' => 'We\'re sorry to hear about your experience. Your feedback is valuable to us, and we want to make things right. Please know we take your concerns seriously.',
            'formal' => 'We thank you for your review. Your feedback has been recorded and will be reviewed by our quality team. We are committed to maintaining the highest standards for Tunisian olive oil.',
            'friendly' => 'Thanks so much for sharing your thoughts! We\'re glad you took the time to let us know. Your feedback helps us make our olive oil even better for everyone.',
        ];

        $response = $templates[$tone] ?? $templates['professional'];

        $suggestion = AdminSuggestedResponse::create([
            'feedback_id' => $feedback->id,
            'suggested_response' => $response,
            'tone' => $tone,
            'confidence' => 0.60,
        ]);

        return [
            'success' => true,
            'suggestion' => $suggestion,
            'fallback' => true,
        ];
    }
}
