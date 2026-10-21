<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Consumer\FeedbackRequest;
use App\Models\Consumer\Feedback;
use App\Models\Distribution\OilProduct;
use App\Services\Consumer\FeedbackClassifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FeedbackController extends Controller
{
    private function resolveProduct(int $product): OilProduct
    {
        $record = OilProduct::findOrFail($product);
        abort_unless($record->isPubliclyVisible(), 404);

        return $record;
    }

    public function store(int $product, FeedbackRequest $request, FeedbackClassifier $classifier)
    {
        $this->resolveProduct($product);
        $user = $request->user();
        $data = $request->validated();
        $analysis = $classifier->classify($data['comment'] ?? '', $data['rating']);

        // One active feedback per consumer per product: submit again to update.
        $feedback = Feedback::updateOrCreate(
            ['oil_product_id' => $product, 'consumer_user_id' => $user->id],
            [
                'rating' => $data['rating'], 'comment' => $data['comment'] ?? null, 'status' => 'visible',
                'ai_category' => $analysis['category'], 'ai_sentiment' => $analysis['sentiment'], 'ai_priority' => $analysis['priority'],
            ]
        );

        return redirect()->route('trace.show', $product)
            ->with('success', $feedback->wasRecentlyCreated ? 'Thank you! Your review was recorded.' : 'Your review was updated.');
    }

    public function edit(Request $request, Feedback $feedback)
    {
        Gate::authorize('update', $feedback);

        return view('consumer.feedback.edit', ['feedback' => $feedback]);
    }

    public function update(FeedbackRequest $request, Feedback $feedback, FeedbackClassifier $classifier)
    {
        Gate::authorize('update', $feedback);
        $data = $request->validated();
        $analysis = $classifier->classify($data['comment'] ?? '', $data['rating']);
        $feedback->update([
            'rating' => $data['rating'], 'comment' => $data['comment'] ?? null,
            'ai_category' => $analysis['category'], 'ai_sentiment' => $analysis['sentiment'], 'ai_priority' => $analysis['priority'],
        ]);

        return redirect()->route('trace.show', $feedback->oil_product_id)->with('success', 'Your review was updated.');
    }

    public function destroy(Request $request, Feedback $feedback)
    {
        Gate::authorize('delete', $feedback);
        $productId = $feedback->oil_product_id;
        $feedback->delete();

        return redirect()->route('trace.show', $productId)->with('success', 'Your review was deleted.');
    }
}
