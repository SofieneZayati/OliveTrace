<?php

namespace App\Http\Controllers\Consumer\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consumer\ConsumerNotification;
use App\Models\Consumer\Feedback;
use App\Services\Consumer\AdminSuggestedResponseService;
use App\Services\Consumer\SlaDashboardService;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SlaDashboardController extends Controller
{
    public function __construct(private SlaDashboardService $slaService) {}

    public function index(Request $request)
    {
        Gate::authorize('access-admin');

        $data = $request->validate(['period' => ['nullable', 'in:7d,30d,90d']]);
        $period = $data['period'] ?? '30d';
        $interval = match ($period) {
            '7d' => CarbonInterval::days(7),
            '30d' => CarbonInterval::days(30),
            '90d' => CarbonInterval::days(90),
            default => CarbonInterval::days(30),
        };

        $metrics = $this->slaService->getMetrics($interval);
        $adminWorkload = $this->slaService->getAdminWorkload();

        return view('consumer.admin.sla-dashboard', [
            'metrics' => $metrics,
            'adminWorkload' => $adminWorkload,
            'period' => $period,
            'urgentNotifications' => ConsumerNotification::where('consumer_user_id', $request->user()->id)
                ->where('type', 'complaint_urgent')->latest()->take(10)->get(),
        ]);
    }

    public function getSuggestedResponse(Feedback $feedback, AdminSuggestedResponseService $aiService)
    {
        Gate::authorize('access-admin');

        $data = request()->validate(['tone' => ['nullable', Rule::in(AdminSuggestedResponseService::TONES)]]);
        $tone = $data['tone'] ?? 'professional';
        $result = $aiService->generate($feedback, $tone);

        if (request()->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 503);
        }

        return back()->with($result['success'] ? 'success' : 'error', $result['suggestion']->suggested_response ?? 'Erreur');
    }

    public function getMultipleTones(Feedback $feedback, AdminSuggestedResponseService $aiService)
    {
        Gate::authorize('access-admin');

        $results = $aiService->generateMultipleTones($feedback);

        return response()->json($results);
    }
}
