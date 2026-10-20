<?php

namespace App\Http\Controllers\Distribution;

use App\Http\Controllers\Controller;
use App\Http\Requests\Distribution\DistributorDashboardRequest;
use App\Services\DistributorDashboardService;
use Illuminate\View\View;

class DistributorDashboardController extends Controller
{
    public function __invoke(DistributorDashboardRequest $request, DistributorDashboardService $dashboard): View
    {
        $admin = $request->user()->isAdmin();
        $filters = $request->validated();

        return view('distribution.dashboard', [
            'metrics' => $dashboard->metrics(
                $admin ? null : (int) $request->user()->id,
                $filters['date_from'] ?? null,
                $filters['date_to'] ?? null,
            ),
            'filters' => $filters,
            'admin' => $admin,
        ]);
    }
}
