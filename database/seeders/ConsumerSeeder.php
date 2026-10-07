<?php

namespace Database\Seeders;

use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use App\Models\User;
use App\Services\Consumer\FeedbackClassifier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

// Module 5 (Aymen) demo data. Feedback and complaints anchor on Hana's
// oil_products: when that table (or its rows) is not present yet, the seeder
// skips gracefully instead of inventing another module's records.
class ConsumerSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('oil_products') || \DB::table('oil_products')->count() === 0) {
            $this->command->info('ConsumerSeeder: no oil products yet, skipping demo reviews and complaints.');

            return;
        }

        $consumer = User::where('email', 'consumer@test.com')->first();
        if (! $consumer) {
            return;
        }

        $classifier = app(FeedbackClassifier::class);
        $productId = (int) \DB::table('oil_products')->orderBy('id')->value('id');

        if (! Feedback::where('oil_product_id', $productId)->where('consumer_user_id', $consumer->id)->exists()) {
            $analysis = $classifier->classify('Excellent fruity Chemlali oil, fresh peppery taste. Highly recommended!', 5);
            Feedback::create([
                'oil_product_id' => $productId, 'consumer_user_id' => $consumer->id,
                'rating' => 5, 'comment' => 'Excellent fruity Chemlali oil, fresh peppery taste. Highly recommended!',
                'status' => 'visible',
                'ai_category' => $analysis['category'], 'ai_sentiment' => $analysis['sentiment'], 'ai_priority' => $analysis['priority'],
            ]);
        }

        if (! Complaint::where('oil_product_id', $productId)->where('consumer_user_id', $consumer->id)->exists()) {
            $analysis = $classifier->classify('Bottle cap was loose on arrival, a little oil leaked in the box');
            Complaint::create([
                'oil_product_id' => $productId, 'consumer_user_id' => $consumer->id,
                'subject' => 'Bottle cap was loose on arrival', 'description' => 'Bottle cap was loose on arrival, a little oil leaked in the box.',
                'status' => 'open',
                'ai_category' => $analysis['category'], 'ai_sentiment' => $analysis['sentiment'], 'ai_priority' => $analysis['priority'],
            ]);
        }
    }
}
