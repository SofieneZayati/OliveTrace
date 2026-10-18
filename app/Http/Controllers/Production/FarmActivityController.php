<?php

namespace App\Http\Controllers\Production;

use App\Enums\FarmActivityStatus;
use App\Enums\FarmActivityType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Production\FarmActivityRequest;
use App\Repositories\Production\Farms;
use App\Services\Production\FarmJournal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class FarmActivityController extends Controller
{
    public function index(Request $request, Farms $farms, int $farm)
    {
        $record = $farms->find($farm);
        Gate::authorize('view', $record);
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['planned', 'completed', 'cancelled', 'overdue'])],
            'type' => ['nullable', Rule::enum(FarmActivityType::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $query = $record->activities();
        if (($filters['status'] ?? '') === 'overdue') {
            $query->where('status', 'planned')->whereDate('planned_on', '<', today());
        } elseif ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['type'] ?? null) {
            $query->where('type', $filters['type']);
        }

        return view('production.activities.index', [
            'farm' => $record, 'admin' => $request->routeIs('admin.*'),
            'activities' => $query->orderByDesc('planned_on')->orderByDesc('id')->paginate(10)->withQueryString(),
            'counts' => [
                'planned' => $record->activities()->where('status', 'planned')->count(),
                'overdue' => $record->activities()->where('status', 'planned')->whereDate('planned_on', '<', today())->count(),
                'completed' => $record->activities()->where('status', 'completed')->count(),
                'water' => $record->activities()->where('status', 'completed')->where('type', 'irrigation')->whereBetween('completed_on', [today()->startOfMonth(), today()])->sum('water_m3'),
            ],
        ]);
    }

    public function create(Request $request, Farms $farms, FarmJournal $journal, int $farm)
    {
        $record = $farms->find($farm);
        $journal->authorizeWrite($request->user(), $record);

        return view('production.activities.form', ['farm' => $record, 'activity' => null]);
    }

    public function store(FarmActivityRequest $request, Farms $farms, FarmJournal $journal, int $farm)
    {
        $journal->create($request->user(), $farms->find($farm), $request->validated());

        return to_route('producer.farms.activities.index', $farm)->with('success', 'Activity planned. Complete it after the work has been recorded.');
    }

    public function edit(Request $request, Farms $farms, FarmJournal $journal, int $farm, int $activity)
    {
        $record = $farms->find($farm);
        $journal->authorizeWrite($request->user(), $record);
        $entry = $record->activities()->findOrFail($activity);
        abort_unless($entry->status === FarmActivityStatus::Planned, 403);

        return view('production.activities.form', ['farm' => $record, 'activity' => $entry]);
    }

    public function update(FarmActivityRequest $request, Farms $farms, FarmJournal $journal, int $farm, int $activity)
    {
        $journal->update($request->user(), $farms->find($farm), $activity, $request->validated());

        return to_route('producer.farms.activities.index', $farm)->with('success', 'Planned activity updated.');
    }

    public function status(Request $request, Farms $farms, FarmJournal $journal, int $farm, int $activity)
    {
        $journal->transition($request->user(), $farms->find($farm), $activity, $request->only('status', 'completed_on', 'water_m3'));

        return to_route('producer.farms.activities.index', $farm)->with('success', 'Activity status updated. The journal keeps its history.');
    }
}
