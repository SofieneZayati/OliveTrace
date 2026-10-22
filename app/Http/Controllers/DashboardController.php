<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Consumer\Complaint;
use App\Models\Consumer\Feedback;
use App\Models\Distribution\OilProduct;
use App\Services\DistributorDashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        DistributorDashboardService $dashboard
    ): View {
        $metrics = null;
        $filters = [];
        $myReviews = collect();
        $myComplaints = collect();
        $topProducts = collect();

        if ($request->user()->isAdmin() || $request->user()->role === Role::Distributor) {
            $filters = $request->validate([
                'date_from' => ['nullable', 'date'],
                'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            ]);
            $metrics = $dashboard->metrics(
                $request->user()->isAdmin() ? null : (int) $request->user()->id,
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null,
            );
        }

        if ($request->user()->role === Role::Consumer) {
            $myReviews = Feedback::where('consumer_user_id', $request->user()->id)
                ->with('product')->latest()->take(3)->get();
            $myComplaints = Complaint::where('consumer_user_id', $request->user()->id)
                ->with('product')->latest()->take(3)->get();
            $topProducts = Feedback::visible()
                ->whereIn('oil_product_id', OilProduct::publiclyVisible()->select('id'))
                ->with('product')
                ->selectRaw('oil_product_id, AVG(rating) as avg_rating, COUNT(*) as review_count')
                ->groupBy('oil_product_id')->orderByDesc('avg_rating')->take(3)->get();
        }

        return view('dashboard', compact('metrics', 'filters', 'myReviews', 'myComplaints', 'topProducts'));
    }
}
