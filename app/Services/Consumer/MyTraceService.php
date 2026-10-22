<?php

namespace App\Services\Consumer;

use App\Models\Consumer\Complaint;
use App\Models\Consumer\ConsumerNotification;
use App\Models\Consumer\Feedback;
use App\Models\Consumer\VerifiedPurchase;
use App\Models\Distribution\OilProduct;
use Illuminate\Support\Collection;

class MyTraceService
{
    public function getConsumerDashboard(int $consumerId): array
    {
        $feedbacks = Feedback::with('product')
            ->where('consumer_user_id', $consumerId)
            ->visible()
            ->latest()
            ->get();

        $complaints = Complaint::with('product')
            ->where('consumer_user_id', $consumerId)
            ->latest()
            ->get();

        $notifications = ConsumerNotification::where('consumer_user_id', $consumerId)
            ->latest()
            ->take(20)
            ->get();

        $verifiedPurchases = VerifiedPurchase::with('product', 'shipment')
            ->where('consumer_user_id', $consumerId)
            ->latest()
            ->get();

        $scannedProducts = $this->getScannedProducts($consumerId);

        return [
            'feedbacks' => $feedbacks,
            'complaints' => $complaints,
            'notifications' => $notifications,
            'unread_notifications' => ConsumerNotification::unreadCount($consumerId),
            'verified_purchases' => $verifiedPurchases,
            'scanned_products' => $scannedProducts,
            'stats' => [
                'total_reviews' => $feedbacks->count(),
                'avg_rating' => $feedbacks->isNotEmpty() ? round($feedbacks->avg('rating'), 1) : 0,
                'total_complaints' => $complaints->count(),
                'open_complaints' => $complaints->filter(fn ($complaint) => ! $complaint->status->isClosed())->count(),
                'verified_purchases' => $verifiedPurchases->whereNotNull('approved_at')->count(),
                'scanned_products' => $scannedProducts->count(),
            ],
        ];
    }

    public function getScannedProducts(int $consumerId): Collection
    {
        // In a real app, this would track QR scans
        // For now, return products with feedback or complaints
        $feedbackProducts = Feedback::where('consumer_user_id', $consumerId)
            ->pluck('oil_product_id');
        $complaintProducts = Complaint::where('consumer_user_id', $consumerId)
            ->pluck('oil_product_id');

        $productIds = $feedbackProducts->merge($complaintProducts)->unique();

        return OilProduct::with(['shipments' => fn ($q) => $q->where('status', 'delivered')])
            ->publiclyVisible()
            ->whereIn('id', $productIds)
            ->get();
    }

    public function generateShareCard(int $productId, string $slug): array
    {
        $product = OilProduct::publiclyVisible()->findOrFail($productId);
        $timeline = app(ConsumerTrace::class)->forSlug($product->slug);
        $traceUrl = route('trace.show', $product->slug);

        return [
            'product_name' => $product->name,
            'brand' => $product->brand,
            'bottle_volume' => $product->bottle_volume_ml,
            'image_url' => $timeline['image_url'],
            'trace_url' => $traceUrl,
            'qr_svg' => $product->qrSvg(),
            'avg_rating' => Feedback::where('oil_product_id', $product->id)->visible()->avg('rating'),
            'review_count' => Feedback::where('oil_product_id', $product->id)->visible()->count(),
            'verified' => $timeline['verification']['verified'] ?? false,
        ];
    }
}
