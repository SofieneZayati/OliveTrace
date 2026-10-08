<?php

namespace App\Services\Consumer;

use App\Enums\Consumer\FeedbackCategory;
use App\Enums\Consumer\FeedbackPriority;
use App\Enums\Consumer\FeedbackSentiment;

// Module 5 advanced AI feature: feedback / complaint classifier.
//
// Input:  free-text review or complaint (French or English) plus the 1-5 rating when available.
// Output: suggested category, sentiment and priority stored on the record. The admin always
//         confirms moderation and status decisions; suggestions never change data by themselves.
//
// Design: a self-contained heuristic classifier (keyword signals + rating baseline). It runs
// fully offline with no API key, so the demo and the normal CRUD workflow keep working even
// when no AI provider is configured. Limitation: it detects explicit keywords, not irony,
// sarcasm or mixed multi-topic texts; low-confidence cases fall back to neutral / normal / other.
class FeedbackClassifier
{
    /** @var array<string, list<string>> keyword signals per category (French + English). */
    private const CATEGORY_SIGNALS = [
        'authenticity' => ['fake', 'fraud', 'counterfeit', 'contrefaçon', 'faux', 'fraude', 'adultéré', 'adulterated', 'mislabeled', 'mensonge', 'lie', 'greenwash'],
        'delivery' => ['delivery', 'shipping', 'shipment', 'livraison', 'colis', 'transport', 'arrived broken', 'late', 'retard', 'cassé', 'endommagé', 'damaged'],
        'packaging' => ['packaging', 'bottle', 'bouteille', 'emballage', 'cap', 'bouchon', 'label', 'étiquette', 'leak', 'fuite', 'seal', 'sceau'],
        'taste' => ['taste', 'goût', 'flavor', 'flavour', 'bitter', 'amer', 'peppery', 'poivré', 'fruity', 'fruité', 'rancid', 'rance', 'délicieux', 'delicious', 'bland', 'fade'],
        'quality' => ['quality', 'qualité', 'acidity', 'acidité', 'pure', 'pur', 'extra virgin', 'vierge', 'premium', 'excellent', 'terrible', 'mauvais', 'bad', 'awful', 'horrible'],
        'price' => ['price', 'prix', 'expensive', 'cher', 'cheap', 'pas cher', 'value', 'rapport', 'cost', 'coût', 'overpriced'],
        'service' => ['service', 'support', 'customer', 'client', 'staff', 'personnel', 'response', 'réponse', 'attente', 'waiting', 'rude', 'impoli', 'helpful', 'aimable'],
    ];

    /** @var array<string, list<string>> */
    private const SENTIMENT_SIGNALS = [
        'positive' => ['excellent', 'love', 'adore', 'great', 'super', 'parfait', 'perfect', 'wonderful', 'merveilleux', 'amazing', 'délicieux', 'delicious', 'bravo', 'merci', 'thank', 'recommend', 'recommande', 'satisfied', 'satisfait', 'superb'],
        'negative' => ['terrible', 'awful', 'horrible', 'hate', 'déteste', 'déçu', 'disappointed', 'disappointing', 'décevant', 'bad', 'mauvais', 'worst', 'pire', 'never again', 'plus jamais', 'refund', 'remboursement', 'complaint', 'plainte', 'problem', 'problème', 'broken', 'cassé', 'rancid', 'rance', 'fake', 'faux', 'fraud', 'fraude', 'sick', 'malade', 'disgusting', 'dégoûtant'],
    ];

    /** @var list<string> safety signals that escalate a complaint to urgent. */
    private const URGENT_SIGNALS = ['glass', 'verre', 'metal', 'métal', 'sick', 'malade', 'poison', 'empoisonné', 'allergy', 'allergie', 'hospital', 'hôpital', 'emergency', 'urgence', 'unsafe', 'dangereux'];

    /** @var list<string> fraud signals that escalate a complaint to high priority. */
    private const HIGH_SIGNALS = ['fake', 'fraud', 'counterfeit', 'contrefaçon', 'faux', 'fraude', 'adulterated', 'adultéré', 'lawyer', 'avocat', 'authorities', 'autorités', 'media', 'médias', 'lawsuit', 'procès'];

    /**
     * @return array{category: FeedbackCategory, sentiment: FeedbackSentiment, priority: FeedbackPriority, note: string}
     */
    public function classify(string $text, ?int $rating = null): array
    {
        $haystack = mb_strtolower($text);

        $category = $this->detectCategory($haystack);
        $sentiment = $this->detectSentiment($haystack, $rating);
        $priority = $this->detectPriority($haystack, $rating, $sentiment);

        return [
            'category' => $category,
            'sentiment' => $sentiment,
            'priority' => $priority,
            'note' => sprintf(
                'Automatic suggestion (%s, %s, %s priority). Advisory only: the admin confirms every moderation decision.',
                $category->label(), $sentiment->label(), $priority->label()
            ),
        ];
    }

    private function detectCategory(string $haystack): FeedbackCategory
    {
        $best = FeedbackCategory::Other;
        $bestScore = 0;

        foreach (self::CATEGORY_SIGNALS as $category => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, $keyword)) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = FeedbackCategory::from($category);
            }
        }

        return $best;
    }

    private function detectSentiment(string $haystack, ?int $rating): FeedbackSentiment
    {
        $positive = $this->countSignals($haystack, self::SENTIMENT_SIGNALS['positive']);
        $negative = $this->countSignals($haystack, self::SENTIMENT_SIGNALS['negative']);

        // The 1-5 rating is a baseline; explicit text signals override it.
        if ($rating !== null && $positive === 0 && $negative === 0) {
            return match (true) {
                $rating <= 2 => FeedbackSentiment::Negative,
                $rating === 3 => FeedbackSentiment::Neutral,
                default => FeedbackSentiment::Positive,
            };
        }

        if ($negative > $positive) {
            return FeedbackSentiment::Negative;
        }

        if ($positive > $negative) {
            return FeedbackSentiment::Positive;
        }

        if ($rating !== null) {
            return $rating <= 2 ? FeedbackSentiment::Negative : ($rating === 3 ? FeedbackSentiment::Neutral : FeedbackSentiment::Positive);
        }

        return FeedbackSentiment::Neutral;
    }

    private function detectPriority(string $haystack, ?int $rating, FeedbackSentiment $sentiment): FeedbackPriority
    {
        if ($this->countSignals($haystack, self::URGENT_SIGNALS) > 0) {
            return FeedbackPriority::Urgent;
        }

        if ($this->countSignals($haystack, self::HIGH_SIGNALS) > 0) {
            return FeedbackPriority::High;
        }

        if (($rating !== null && $rating <= 2) || $sentiment === FeedbackSentiment::Negative) {
            return FeedbackPriority::High;
        }

        if ($sentiment === FeedbackSentiment::Positive) {
            return FeedbackPriority::Low;
        }

        return FeedbackPriority::Normal;
    }

    /** @param list<string> $keywords */
    private function countSignals(string $haystack, array $keywords): int
    {
        $count = 0;
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                $count++;
            }
        }

        return $count;
    }
}
