<?php

namespace App\Http\Controllers\Distribution;

use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Distribution\ListShipmentFiltersRequest;
use App\Http\Requests\Distribution\StoreShipmentRequest;
use App\Http\Requests\Distribution\UpdateShipmentRequest;
use App\Http\Requests\Distribution\UpdateShipmentStatusRequest;
use App\Models\Distribution\DistributorProfile;
use App\Models\Distribution\OilProduct;
use App\Models\Distribution\Shipment;
use App\Services\Distribution\ShipmentStatusTransition;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShipmentController extends Controller
{
    use AuthorizesRequests;

    public function index(ListShipmentFiltersRequest $request): View
    {
        $filters = $request->validated();
        $admin = $request->routeIs('admin.*');
        $shipments = Shipment::query()
            ->with(['oilProduct', 'distributorProfile.user'])
            ->when(! $admin, fn ($query) => $query->forDistributorUser($request->user()->id))
            ->when($admin && isset($filters['owner_id']), fn ($query) => $query->whereHas('distributorProfile', fn ($profile) => $profile->where('user_id', (int) $filters['owner_id'])))
            ->when(isset($filters['status']), fn ($query) => $query->byStatus($filters['status']))
            ->when(isset($filters['date_from']) || isset($filters['date_to']), fn ($query) => $query->departureDateBetween($filters['date_from'] ?? null, $filters['date_to'] ?? null))
            ->latest('departure_date')
            ->paginate(15)
            ->withQueryString();

        return view('distribution.shipments.index', compact('shipments', 'admin', 'filters'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Shipment::class);
        $profile = $this->profileFor($request);
        if ($profile === null || ! $profile->is_active) {
            return redirect()->route('distributor.profile.create')->with('error', 'Create an active distributor profile before planning shipments.');
        }

        return view('distribution.shipments.form', [
            'shipment' => null,
            'products' => OilProduct::query()->active()->orderBy('name')->get(),
            'allowedStatuses' => [ShipmentStatus::Planned],
        ]);
    }

    public function store(StoreShipmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Shipment::class);
        $profile = $this->profileFor($request);
        if ($profile === null || ! $profile->is_active) {
            return redirect()->route('distributor.profile.create')->with('error', 'Create an active distributor profile before planning shipments.');
        }

        $shipment = new Shipment($request->validated());
        $shipment->distributor_profile_id = $profile->id;
        $shipment->save();

        return redirect()->route('distributor.shipments.show', $shipment)->with('success', 'Shipment planned.');
    }

    public function show(Shipment $shipment): View
    {
        $this->authorize('view', $shipment);
        $shipment->load(['oilProduct', 'distributorProfile.user']);

        return view('distribution.shipments.show', compact('shipment'));
    }

    public function edit(Shipment $shipment, ShipmentStatusTransition $transitions): View
    {
        $this->authorize('update', $shipment);

        return view('distribution.shipments.form', [
            'shipment' => $shipment,
            'products' => OilProduct::query()->active()->orderBy('name')->get(),
            'allowedStatuses' => collect(ShipmentStatus::cases())
                ->filter(fn (ShipmentStatus $next): bool => $transitions->allows($shipment->status, $next))
                ->values(),
        ]);
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment): RedirectResponse
    {
        $this->authorize('update', $shipment);
        if ($request->validated('status') === ShipmentStatus::Cancelled->value) {
            $this->authorize('cancel', $shipment);
        }
        $shipment->fill($request->validated())->save();

        return redirect()->route('distributor.shipments.show', $shipment)->with('success', 'Shipment updated.');
    }

    public function updateStatus(UpdateShipmentStatusRequest $request, Shipment $shipment): RedirectResponse
    {
        $data = $request->validated();
        $this->authorize($data['status'] === ShipmentStatus::Cancelled->value ? 'cancel' : 'update', $shipment);
        $shipment->status = ShipmentStatus::from($data['status']);
        if ($shipment->status === ShipmentStatus::Delivered && $shipment->arrival_date === null) {
            return back()->withErrors(['status' => 'Set an arrival date before marking this shipment as delivered.']);
        }
        $shipment->save();

        return back()->with('success', 'Shipment status updated.');
    }

    private function profileFor(Request $request): ?DistributorProfile
    {
        return DistributorProfile::query()->where('user_id', $request->user()->id)->first();
    }
}
