<?php

namespace Tests\Unit;

use App\Enums\Consumer\FeedbackCategory;
use App\Enums\Consumer\FeedbackPriority;
use App\Enums\Consumer\FeedbackSentiment;
use App\Services\Consumer\FeedbackClassifier;
use PHPUnit\Framework\TestCase;

class FeedbackClassifierTest extends TestCase
{
    private FeedbackClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classifier = new FeedbackClassifier;
    }

    public function test_positive_review_is_classified_with_low_priority(): void
    {
        $result = $this->classifier->classify('Excellent fruity oil, delicious peppery taste, I love it!', 5);

        $this->assertSame(FeedbackSentiment::Positive, $result['sentiment']);
        $this->assertSame(FeedbackPriority::Low, $result['priority']);
        $this->assertArrayHasKey('note', $result);
    }

    public function test_fraud_mention_escalates_to_high_priority_authenticity(): void
    {
        $result = $this->classifier->classify('This tastes fake, I suspect fraud, counterfeit oil', 1);

        $this->assertSame(FeedbackCategory::Authenticity, $result['category']);
        $this->assertSame(FeedbackSentiment::Negative, $result['sentiment']);
        $this->assertSame(FeedbackPriority::High, $result['priority']);
    }

    public function test_safety_mention_escalates_to_urgent(): void
    {
        $result = $this->classifier->classify('Found a piece of glass inside the bottle, my child could have been sick');

        $this->assertSame(FeedbackPriority::Urgent, $result['priority']);
        $this->assertSame(FeedbackSentiment::Negative, $result['sentiment']);
    }

    public function test_packaging_issue_is_detected(): void
    {
        $result = $this->classifier->classify('The bottle cap was loose and oil leaked from the packaging', 2);

        $this->assertSame(FeedbackCategory::Packaging, $result['category']);
    }

    public function test_empty_text_falls_back_to_rating_and_neutral_defaults(): void
    {
        $low = $this->classifier->classify('', 2);
        $this->assertSame(FeedbackSentiment::Negative, $low['sentiment']);

        $mid = $this->classifier->classify('');
        $this->assertSame(FeedbackSentiment::Neutral, $mid['sentiment']);
        $this->assertSame(FeedbackCategory::Other, $mid['category']);
        $this->assertSame(FeedbackPriority::Normal, $mid['priority']);
    }
}
