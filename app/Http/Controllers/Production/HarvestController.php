<?php

namespace App\Http\Controllers\Production;

use App\Enums\HarvestMethod;
use App\Enums\HarvestStatus;
use App\Enums\MillRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Production\HarvestRequest;
use App\Models\Mill;
use App\Models\Production\MillRequest;
use App\Repositories\Production\Farms;
use App\Repositories\Production\Harvests;
use App\Services\Production\HarvestManagement;
use App\Services\Production\HarvestOilAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class HarvestController extends Controller
{
    public function __construct(private Harvests $harvests, private Farms $farms) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(HarvestStatus::class)],
            'method' => ['nullable', Rule::enum(HarvestMethod::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $admin = $request->routeIs('admin.*');

        return view('production.harvests.index', [
            'harvests' => $this->harvests->paginate($filters, (int) ($filters['page'] ?? 1), $admin ? null : $request->user()->id),
            'admin' => $admin,
        ]);
    }

    public function create(Request $request)
    {
        $farms = $this->farms->selectableForUser($request->user()->id);
        if (! $farms) {
            return redirect()->route('producer.farms.create')->with('error', 'Add an active farm before declaring a harvest.');
        }

        return view('production.harvests.form', [
            'harvest' => null,
            'farms' => $farms,
            'admin' => false,
            'presetFarmId' => $request->integer('farm') ?: null,
        ]);
    }

    public function store(HarvestRequest $request, HarvestManagement $management)
    {
        $harvest = $management->createHarvest($request->user(), $request->validated());

        return redirect()->route('producer.harvests.show', $harvest->id)->with('success', 'Harvest declared. You can now schedule a request to a mill.');
    }

    public function estimate(Request $request, int $harvest, HarvestOilAssistant $assistant)
    {
        $record = $this->harvests->find($harvest);
        Gate::authorize('view', $record);

        return response()->view('production.harvests.show', $this->show($request, $harvest)->getData() + [
            'estimateResult' => $assistant->estimate($record),
        ])->header('Cache-Control', 'no-store');
    }

    public function show(Request $request, int $harvest)
    {
        $record = $this->harvests->find($harvest);
        Gate::authorize('view', $record);
        $admin = $request->routeIs('admin.*');
        $activeRequest = $record->millRequests->first(
            fn (MillRequest $pending) => in_array($pending->status, [MillRequestStatus::Pending, MillRequestStatus::Accepted], true)
        );

        return view('production.harvests.show', [
            'harvest' => $record,
            'admin' => $admin,
            'mills' => $admin ? collect() : Mill::orderBy('name')->get(),
            'activeRequest' => $activeRequest,
            'canSend' => ! $admin
                && $record->status !== HarvestStatus::Milled
                && $activeRequest === null
                && $request->user()->can('create', [MillRequest::class, $record]),
        ]);
    }

    public function edit(Request $request, int $harvest)
    {
        $record = $this->harvests->find($harvest);
        Gate::authorize('update', $record);
        $farms = collect($this->farms->selectableForUser($request->user()->id));
        if (! $farms->contains('id', $record->farm_id)) {
            $farms->push($record->farm);
        }

        return view('production.harvests.form', [
            'harvest' => $record,
            'farms' => $farms->all(),
            'admin' => false,
            'presetFarmId' => $record->farm_id,
        ]);
    }

    public function update(HarvestRequest $request, HarvestManagement $management, int $harvest)
    {
        $record = $this->harvests->find($harvest);
        $management->updateHarvest($request->user(), $record, $request->validated());

        return redirect()->route('producer.harvests.show', $record->id)->with('success', 'Harvest updated.');
    }

    public function destroy(Request $request, HarvestManagement $management, int $harvest)
    {
        $management->deleteHarvest($request->user(), $this->harvests->find($harvest));

        return redirect()->route($request->routeIs('admin.*') ? 'admin.harvests.index' : 'producer.harvests.index')
            ->with('success', 'Harvest deleted.');
    }
}
