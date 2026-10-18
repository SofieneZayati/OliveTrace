<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Repositories\Production\Farms;
use App\Services\Production\FarmActionPlans;
use App\Services\Production\FarmSustainabilityAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FarmAdviceReportController extends Controller
{
    public function index(Request $request, Farms $farms, int $farm)
    {
        $record = $farms->find($farm);
        Gate::authorize('view', $record);
        $request->validate(['page' => ['sometimes', 'integer', 'min:1']]);

        return view('production.reports.index', [
            'farm' => $record, 'admin' => $request->routeIs('admin.*'),
            'reports' => $record->adviceReports()->withCount('activities')->latest('id')->paginate(10),
        ]);
    }

    public function store(Request $request, Farms $farms, FarmActionPlans $plans, FarmSustainabilityAssistant $assistant, int $farm)
    {
        $result = $plans->generate($request->user(), $farms->find($farm), $assistant);
        $prefix = $request->routeIs('admin.*') ? 'admin' : 'producer';
        if (! $result['available']) {
            return to_route($prefix.'.farms.reports.index', $farm)->with('error', $result['message']);
        }

        return to_route($prefix.'.farms.reports.show', [$farm, $result['report']->id])->with('success', 'AI report saved. Review the suggestions before planning any work.');
    }

    public function show(Request $request, Farms $farms, int $farm, int $report)
    {
        $record = $farms->find($farm);
        Gate::authorize('view', $record);
        $saved = $record->adviceReports()->with('activities')->findOrFail($report);

        return response()->view('production.reports.show', [
            'farm' => $record, 'report' => $saved, 'admin' => $request->routeIs('admin.*'),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function adopt(Request $request, Farms $farms, FarmActionPlans $plans, int $farm, int $report, int $recommendation)
    {
        $plans->adopt($request->user(), $farms->find($farm), $report, $recommendation, $request->only('title', 'planned_on', 'notes', 'reviewed'));

        return to_route('producer.farms.activities.index', $farm)->with('success', 'Reviewed recommendation added to your plan. Repeat submissions reuse the same activity.');
    }
}
