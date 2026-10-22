<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Consumer\ConsumerNotification;
use App\Models\Consumer\Feedback;
use App\Models\Consumer\VerifiedPurchase;
use App\Models\Distribution\OilProduct;
use App\Services\Consumer\MyTraceService;
use Illuminate\Http\Request;

class MyTraceController extends Controller
{
    public function __construct(private MyTraceService $traceService) {}

    public function index(Request $request)
    {
        $data = $this->traceService->getConsumerDashboard($request->user()->id);

        return view('consumer.my-trace.index', $data);
    }

    public function notifications(Request $request)
    {
        $notifications = ConsumerNotification::where('consumer_user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('consumer.my-trace.notifications', ['notifications' => $notifications]);
    }

    public function markNotificationRead(Request $request, ConsumerNotification $notification)
    {
        if ($notification->consumer_user_id !== $request->user()->id) {
            abort(403);
        }
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        ConsumerNotification::where('consumer_user_id', $request->user()->id)
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json(['success' => true]);
    }

    public function shareProduct(Request $request, OilProduct $product)
    {
        $traceService = app(MyTraceService::class);
        $shareData = $traceService->generateShareCard($product->id, $product->slug);

        return view('consumer.share-card', ['share' => $shareData]);
    }

    public function shareCardApi(Request $request, OilProduct $product)
    {
        $traceService = app(MyTraceService::class);
        $shareData = $traceService->generateShareCard($product->id, $product->slug);

        return response()->json($shareData);
    }

    public function uploadProof(Request $request, OilProduct $product)
    {
        $request->validate([
            'proof_type' => ['required', 'in:receipt,qr_code,invoice'],
            'proof_file' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ]);

        $path = $request->file('proof_file')->store('verified-purchases', 'public');

        VerifiedPurchase::updateOrCreate(
            [
                'consumer_user_id' => $request->user()->id,
                'oil_product_id' => $product->id,
            ],
            [
                'proof_type' => $request->proof_type,
                'proof_path' => $path,
            ]
        );

        // Update feedback verified_purchase flag
        Feedback::where('consumer_user_id', $request->user()->id)
            ->where('oil_product_id', $product->id)
            ->update(['verified_purchase' => true]);

        return back()->with('success', 'Preuve d\'achat enregistrée. Votre avis est maintenant marqué comme achat vérifié.');
    }
}
