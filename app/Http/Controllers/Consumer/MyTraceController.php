<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Consumer\ConsumerNotification;
use App\Models\Consumer\Feedback;
use App\Models\Consumer\VerifiedPurchase;
use App\Models\Distribution\OilProduct;
use App\Services\Consumer\MyTraceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

        return $request->expectsJson() ? response()->json(['success' => true]) : back()->with('success', 'Notification marked as read.');
    }

    public function markAllNotificationsRead(Request $request)
    {
        ConsumerNotification::where('consumer_user_id', $request->user()->id)
            ->where('read', false)
            ->update(['read' => true]);

        return $request->expectsJson() ? response()->json(['success' => true]) : back()->with('success', 'Notifications marked as read.');
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

        abort_unless($product->isPubliclyVisible(), 404);
        $path = $request->file('proof_file')->store('verified-purchases', 'local');
        abort_unless($path, 500, 'The purchase proof could not be stored.');
        $oldPath = null;
        try {
            DB::transaction(function () use ($request, $product, $path, &$oldPath): void {
                $existing = VerifiedPurchase::where('consumer_user_id', $request->user()->id)
                    ->where('oil_product_id', $product->id)->lockForUpdate()->first();
                $oldPath = $existing?->proof_path;
                VerifiedPurchase::updateOrCreate(
                    [
                        'consumer_user_id' => $request->user()->id,
                        'oil_product_id' => $product->id,
                    ],
                    [
                        'proof_type' => $request->proof_type,
                        'proof_path' => $path,
                        'verified_at' => now(),
                        'approved_at' => null,
                        'approved_by_user_id' => null,
                    ]
                );

                // Update feedback verified_purchase flag
                Feedback::where('consumer_user_id', $request->user()->id)
                    ->where('oil_product_id', $product->id)
                    ->update(['verified_purchase' => false]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return back()->with('success', 'Preuve reçue. Un administrateur doit la vérifier avant attribution du badge.');
    }

    public function proof(Request $request, VerifiedPurchase $purchase)
    {
        abort_unless($request->user()->isAdmin() || $purchase->consumer_user_id === $request->user()->id, 403);
        abort_unless($purchase->proof_path && Storage::disk('local')->exists($purchase->proof_path), 404);

        return response()->download(Storage::disk('local')->path($purchase->proof_path), null, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function proofsIndex()
    {
        Gate::authorize('access-admin');

        return view('consumer.admin.purchase-proofs', [
            'purchases' => VerifiedPurchase::with(['consumer', 'product'])->latest()->paginate(15),
        ]);
    }

    public function approveProof(Request $request, VerifiedPurchase $purchase)
    {
        Gate::authorize('access-admin');
        $data = $request->validate(['proof_version' => ['required', 'string', 'size:64']]);
        DB::transaction(function () use ($request, $purchase, $data): void {
            $record = VerifiedPurchase::lockForUpdate()->findOrFail($purchase->id);
            if (! hash_equals(hash('sha256', (string) $record->proof_path), $data['proof_version'])) {
                throw ValidationException::withMessages(['proof_version' => 'The proof was replaced. Review the new file before approving.']);
            }
            abort_unless($record->proof_path && Storage::disk('local')->exists($record->proof_path), 404);
            $record->update(['approved_at' => now(), 'approved_by_user_id' => $request->user()->id]);
            Feedback::where('consumer_user_id', $record->consumer_user_id)->where('oil_product_id', $record->oil_product_id)
                ->update(['verified_purchase' => true]);
        });

        return back()->with('success', 'Purchase proof approved.');
    }
}
