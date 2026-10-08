<?php

namespace App\Http\Controllers\Production;

use App\Enums\FarmStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Production\FarmRequest;
use App\Repositories\Production\Farms;
use App\Repositories\Production\Harvests;
use App\Repositories\Production\ProducerProfiles;
use App\Services\Production\ProductionManagement;
use App\Services\Production\PublicOrigin;
use App\Support\ProductionOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class FarmController extends Controller
{
    public function __construct(private Farms $farms, private ProducerProfiles $profiles) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'], 'governorate' => ['nullable', Rule::in(ProductionOptions::GOVERNORATES)],
            'status' => ['nullable', Rule::enum(FarmStatus::class)], 'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $admin = $request->routeIs('admin.*');

        return view('production.farms.index', [
            'farms' => $this->farms->paginate($filters, (int) ($filters['page'] ?? 1), $admin ? null : $request->user()->id),
            'admin' => $admin,
        ]);
    }

    public function create(Request $request)
    {
        $profile = $this->profiles->forUser($request->user()->id);
        if (! $profile) {
            return redirect()->route('producer.profile.create')->with('error', 'Create your producer profile before adding a farm.');
        }

        return view('production.farms.form', ['farm' => null, 'admin' => false]);
    }

    public function store(FarmRequest $request, ProductionManagement $management)
    {
        $profile = $this->profiles->forUser($request->user()->id);
        if (! $profile) {
            return redirect()->route('producer.profile.create')->with('error', 'Create your producer profile first.');
        }
        $farm = $management->createFarm($request->user(), $profile, $request->validated());

        return redirect()->route('producer.farms.show', $farm->id)->with('success', 'Farm created. Its ID can now be used by the harvest module.');
    }

    public function show(Request $request, Harvests $harvests, int $farm)
    {
        $record = $this->farms->find($farm);
        Gate::authorize('view', $record);

        return view('production.farms.show', [
            'farm' => $record,
            'admin' => $request->routeIs('admin.*'),
            'publicOrigin' => app(PublicOrigin::class)->forFarm($record->id),
            'harvests' => $harvests->forFarm($record->id),
        ]);
    }

    public function edit(Request $request, int $farm)
    {
        $record = $this->farms->find($farm);
        Gate::authorize('update', $record);

        return view('production.farms.form', ['farm' => $record, 'admin' => $request->routeIs('admin.*')]);
    }

    public function update(FarmRequest $request, ProductionManagement $management, int $farm)
    {
        $record = $this->farms->find($farm);
        $management->updateFarm($request->user(), $record, $request->validated());

        return redirect()->route($request->routeIs('admin.*') ? 'admin.farms.show' : 'producer.farms.show', $record->id)->with('success', 'Farm updated.');
    }

    public function destroy(Request $request, ProductionManagement $management, int $farm)
    {
        $archived = $management->deleteFarm($request->user(), $this->farms->find($farm));

        return redirect()->route('producer.farms.index')->with('success', $archived ? 'Farm archived: its harvest history was preserved.' : 'Farm deleted.');
    }

    public function archive(Request $request, ProductionManagement $management, int $farm)
    {
        $management->archiveFarm($request->user(), $this->farms->find($farm));

        return redirect()->route('producer.farms.show', $farm)->with('success', 'Farm archived. Existing history remains available.');
    }

    public function status(Request $request, ProductionManagement $management, int $farm)
    {
        $data = $request->validate(['status' => ['required', Rule::enum(FarmStatus::class)]]);
        $management->moderateFarm($request->user(), $this->farms->find($farm), FarmStatus::from($data['status']));

        return redirect()->route('admin.farms.show', $farm)->with('success', 'Farm status updated.');
    }
}
