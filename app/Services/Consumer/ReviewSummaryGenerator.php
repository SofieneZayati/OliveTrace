<?php

namespace App\Services\Consumer;

use App\Models\Consumer\Feedback;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Module 5 advanced AI feature: review summary generator.
//
// Aggregates visible consumer reviews and uses the Groq API to produce a
// concise executive summary highlighting recurring themes, strengths and
// areas for improvement. The admin triggers this on demand from the
// feedback moderation page.
//
// Requires GROQ_API_KEY in .env. Falls back gracefully when unavailable.
class ReviewSummaryGenerator
{
    public function generate(): ?string
    {
        $apiKey = config('services.groq.api_key');
        if (empty($apiKey)) {
            return null;
        }

        $model = config('services.groq.model', 'llama-3.3-70b-versatile');

        // Gather up to 25 recent visible reviews to keep token usage reasonable.
        $reviews = Feedback::with('product')->visible()->latest()->take(25)->get();

        if ($reviews->isEmpty()) {
            return 'Aucun avis consommateur disponible pour générer un résumé.';
        }

        $reviewTexts = $reviews->map(function ($r) {
            $product = $r->product?->name ?? 'Produit #' . $r->oil_product_id;
            return "- [{$r->rating}/5] {$product}: " . ($r->comment ?? '(pas de commentaire)');
        })->implode("\n");

        $avg = round($reviews->avg('rating'), 1);
        $count = $reviews->count();

        $systemPrompt = <<<PROMPT
Tu es un analyste de satisfaction client pour OliveTrace, plateforme de traçabilité d'huile d'olive tunisienne.
Ton rôle est de synthétiser les avis consommateurs en un résumé exécutif clair et structuré.
Le résumé doit:
- Être en français
- Faire 4 à 6 phrases
- Mentionner la note moyenne et le nombre d'avis
- Identifier les points forts récurrents (goût, qualité, authenticité...)
- Identifier les points d'amélioration (emballage, prix, livraison...)
- Rester factuel et basé sur les données
PROMPT;

        $userMessage = <<<MSG
Voici {$count} avis consommateurs récents (note moyenne: {$avg}/5):

{$reviewTexts}

Génère un résumé exécutif de la satisfaction consommateur.
MSG;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage],
                ],
                'temperature' => 0.5,
                'max_tokens' => 400,
            ]);

            if ($response->successful()) {
                return trim($response->json('choices.0.message.content', ''));
            }

            Log::warning('ReviewSummaryGenerator: Groq API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('ReviewSummaryGenerator: request failed', ['error' => $e->getMessage()]);
            return null;
        }
    }
}
