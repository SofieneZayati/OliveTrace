<?php

namespace Database\Seeders;

use App\Enums\Consumer\ComplaintStatus;
use App\Enums\Consumer\FeedbackCategory;
use App\Enums\Consumer\FeedbackPriority;
use App\Enums\Consumer\FeedbackSentiment;
use App\Enums\Consumer\FeedbackStatus;
use App\Enums\Role;
use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use App\Models\Distribution\OilProduct;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;

class ConsumerSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Consumer demo data may only be seeded in local or testing environments.');
        }

        if (! Schema::hasTable('oil_products') || OilProduct::count() === 0) {
            $this->command->info('ConsumerSeeder: no oil products yet, skipping.');

            return;
        }

        $consumer = User::where('email', 'consumer@test.com')->where('role', Role::Consumer)->first();
        if (! $consumer) {
            throw new LogicException('Seed DevelopmentUserSeeder first.');
        }

        $consumerTwo = User::firstOrCreate(['email' => 'consumer_two@test.com'], ['name' => 'Consumer Two', 'password' => Hash::make('OliveTrace123!'), 'role' => Role::Consumer, 'is_active' => true]);
        $consumerThree = User::firstOrCreate(['email' => 'consumer_three@test.com'], ['name' => 'Consumer Three', 'password' => Hash::make('OliveTrace123!'), 'role' => Role::Consumer, 'is_active' => true]);

        // Load products by name so the data is anchored to what DistributionSeeder created
        $productA = OilProduct::where('name', 'Huile d\'olive extra vierge — El Baraka')->first();
        $productB = OilProduct::where('name', 'Chemlali Harvest Selection — 250 ml')->first();
        $productC = OilProduct::where('name', 'Early Press Reserve — Domaine En Nour')->first();
        $productD = OilProduct::where('name', 'Organic Grove Blend — 1L Catering')->first();

        // Fall back to the first available product if names changed
        $fallback = OilProduct::orderBy('id')->first();
        $productA ??= $fallback;
        $productB ??= $fallback;
        $productC ??= $fallback;
        $productD ??= $fallback;

        // ── FEEDBACK ──────────────────────────────────────────────────────────────
        $feedbackData = [
            // 5-star rave for Product A
            [
                'product' => $productA,
                'consumer' => $consumer,
                'rating' => 5,
                'comment' => 'Exceptional quality — deep golden colour, rich peppery finish and a beautiful fruity aroma. Exactly what a Chemlali EVOO should be. Will reorder.',
                'status' => FeedbackStatus::Visible,
                'ai_category' => FeedbackCategory::Taste,
                'ai_sentiment' => FeedbackSentiment::Positive,
                'ai_priority' => FeedbackPriority::Low,
            ],
            // 4-star quality note for Product A
            [
                'product' => $productA,
                'consumer' => $consumerTwo,
                'rating' => 4,
                'comment' => 'Very good oil. The traceability QR code is a great touch — I could see the exact farm and harvest date. Packaging could be slightly better sealed.',
                'status' => FeedbackStatus::Visible,
                'ai_category' => FeedbackCategory::Authenticity,
                'ai_sentiment' => FeedbackSentiment::Positive,
                'ai_priority' => FeedbackPriority::Low,
            ],
            // 5-star for Product B
            [
                'product' => $productB,
                'consumer' => $consumer,
                'rating' => 5,
                'comment' => 'Love the 250 ml size for everyday cooking. Perfect gift too. Taste is top notch, clearly from a well-maintained grove.',
                'status' => FeedbackStatus::Visible,
                'ai_category' => FeedbackCategory::Packaging,
                'ai_sentiment' => FeedbackSentiment::Positive,
                'ai_priority' => FeedbackPriority::Low,
            ],
            // 3-star neutral for Product B — pricing concern
            [
                'product' => $productB,
                'consumer' => $consumerTwo,
                'rating' => 3,
                'comment' => 'Good oil but a bit expensive for a 250 ml bottle. The quality is real but I\'d expect a lower price for this size.',
                'status' => FeedbackStatus::Visible,
                'ai_category' => FeedbackCategory::Price,
                'ai_sentiment' => FeedbackSentiment::Neutral,
                'ai_priority' => FeedbackPriority::Normal,
            ],
            // 5-star for Product C (premium early press)
            [
                'product' => $productC,
                'consumer' => $consumer,
                'rating' => 5,
                'comment' => 'This Early Press Reserve is incredible. I can taste the difference from supermarket oil instantly — almost no bitterness, just clean and buttery. Worth every dinar.',
                'status' => FeedbackStatus::Visible,
                'ai_category' => FeedbackCategory::Quality,
                'ai_sentiment' => FeedbackSentiment::Positive,
                'ai_priority' => FeedbackPriority::Low,
            ],
            // 2-star negative for Product D — delivery issue
            [
                'product' => $productD,
                'consumer' => $consumer,
                'rating' => 2,
                'comment' => 'The oil itself is fine but delivery took 8 days and the box arrived crushed. The 1L bottle survived but it was stressful.',
                'status' => FeedbackStatus::Visible,
                'ai_category' => FeedbackCategory::Delivery,
                'ai_sentiment' => FeedbackSentiment::Negative,
                'ai_priority' => FeedbackPriority::High,
            ],
            // Hidden (moderated out) review for Product A
            [
                'product' => $productA,
                'consumer' => $consumerThree,
                'rating' => 1,
                'comment' => 'This review contains spam content — hidden by admin.',
                'status' => FeedbackStatus::Hidden,
                'ai_category' => FeedbackCategory::Other,
                'ai_sentiment' => FeedbackSentiment::Negative,
                'ai_priority' => FeedbackPriority::Normal,
            ],
        ];

        foreach ($feedbackData as $data) {
            Feedback::firstOrCreate(
                [
                    'oil_product_id' => $data['product']->id,
                    'consumer_user_id' => $data['consumer']->id,
                ],
                [
                    'comment' => $data['comment'],
                    'rating' => $data['rating'],
                    'status' => $data['status'],
                    'ai_category' => $data['ai_category'],
                    'ai_sentiment' => $data['ai_sentiment'],
                    'ai_priority' => $data['ai_priority'],
                ]
            );
        }

        // ── COMPLAINTS ────────────────────────────────────────────────────────────
        $complaintData = [
            // Open complaint — packaging defect (product A)
            [
                'product' => $productA,
                'consumer' => $consumer,
                'subject' => 'Bottle cap was loose on arrival',
                'description' => 'When my order arrived the outer cap on the 750 ml El Baraka bottle was not sealed. A small amount of oil had leaked inside the cardboard box. The bottle was still mostly full but I am worried about contamination or premature oxidation.',
                'status' => ComplaintStatus::Open,
                'admin_response' => null,
                'resolved_at' => null,
                'ai_category' => FeedbackCategory::Packaging,
                'ai_sentiment' => FeedbackSentiment::Negative,
                'ai_priority' => FeedbackPriority::High,
            ],
            // In-review complaint — authenticity concern (product B)
            [
                'product' => $productB,
                'consumer' => $consumerTwo,
                'subject' => 'QR code does not load the traceability page',
                'description' => 'I scanned the QR code on my 250 ml bottle three times with two different phones and it always redirects to a 404 page. I bought this specifically because I wanted to verify the origin.',
                'status' => ComplaintStatus::InReview,
                'admin_response' => 'Thank you for reporting this. We are investigating a routing issue on our trace page. We will update you within 24 hours.',
                'resolved_at' => null,
                'ai_category' => FeedbackCategory::Authenticity,
                'ai_sentiment' => FeedbackSentiment::Negative,
                'ai_priority' => FeedbackPriority::Urgent,
            ],
            // Resolved complaint — taste concern (product C)
            [
                'product' => $productC,
                'consumer' => $consumer,
                'subject' => 'Oil smells slightly rancid',
                'description' => 'My 500 ml Early Press Reserve arrived and had a faint off smell — not strong but noticeable. Could be a storage issue in transit.',
                'status' => ComplaintStatus::Resolved,
                'admin_response' => 'We are very sorry for this experience. After investigation we found an isolated batch from that shipment was stored above recommended temperature during transit. We have sent a replacement bottle and issued a full refund.',
                'resolved_at' => now()->subDay(),
                'ai_category' => FeedbackCategory::Quality,
                'ai_sentiment' => FeedbackSentiment::Negative,
                'ai_priority' => FeedbackPriority::Urgent,
            ],
            // Rejected complaint — product D (unjustified)
            [
                'product' => $productD,
                'consumer' => $consumer,
                'subject' => 'Wrong product sent',
                'description' => 'I ordered the 1L catering bottle but I think I received a smaller one.',
                'status' => ComplaintStatus::Rejected,
                'admin_response' => 'After reviewing your order and delivery photo, the 1L bottle was correctly dispatched and received. The label clearly shows 1000 ml. Please check again — the bottle shape may look smaller than expected.',
                'resolved_at' => now()->subDays(3),
                'ai_category' => FeedbackCategory::Delivery,
                'ai_sentiment' => FeedbackSentiment::Neutral,
                'ai_priority' => FeedbackPriority::Normal,
            ],
        ];

        foreach ($complaintData as $data) {
            Complaint::firstOrCreate(
                [
                    'oil_product_id' => $data['product']->id,
                    'consumer_user_id' => $data['consumer']->id,
                    'subject' => $data['subject'],
                ],
                [
                    'description' => $data['description'],
                    'status' => $data['status'],
                    'admin_response' => $data['admin_response'],
                    'resolved_at' => $data['resolved_at'],
                    'ai_category' => $data['ai_category'],
                    'ai_sentiment' => $data['ai_sentiment'],
                    'ai_priority' => $data['ai_priority'],
                ]
            );
        }
    }
}
