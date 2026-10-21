<?php

namespace App\Http\Controllers;

use App\Enums\Role;
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

        return view('dashboard', compact('metrics', 'filters'));
    }
}
