<?php

namespace App\Http\Controllers\Distribution;

use App\Contracts\OilLotLookup;
use App\Enums\OilProductPublicStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Distribution\ListOilProductFiltersRequest;
use App\Http\Requests\Distribution\StoreOilProductRequest;
use App\Http\Requests\Distribution\UpdateOilProductRequest;
use App\Models\Distribution\OilProduct;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OilProductController extends Controller
{
    use AuthorizesRequests;

    public function index(ListOilProductFiltersRequest $request): View
    {
        $filters = $request->validated();
        $admin = $request->routeIs('admin.*') || $request->user()->isAdmin();
        $products = OilProduct::query()
            ->with('createdBy')
            ->when(! $admin, fn ($query) => $query->ownedBy($request->user()))
            ->when($admin && isset($filters['owner_id']), fn ($query) => $query->ownedBy((int) $filters['owner_id']))
            ->when(isset($filters['status']), fn ($query) => $query->byStatus($filters['status']))
            ->when(isset($filters['date_from']) || isset($filters['date_to']), fn ($query) => $query->packagingDateBetween($filters['date_from'] ?? null, $filters['date_to'] ?? null))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('distribution.products.index', compact('products', 'admin', 'filters'));
    }

    public function create(OilLotLookup $lots): View
    {
        $this->authorize('create', OilProduct::class);

        return view('distribution.products.form', [
            'product' => null,
            'lots' => $lots->available(),
            'admin' => false,
        ]);
    }

    public function store(StoreOilProductRequest $request): RedirectResponse
    {
        $this->authorize('create', OilProduct::class);
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('distribution/products', 'public');
        }

        $product = new OilProduct($data);
        $product->created_by_user_id = $request->user()->id;
        $product->save();

        return redirect()->route('producer.products.show', $product)->with('success', 'Product created.');
    }

    public function show(Request $request, OilProduct $product): View
    {
        $this->authorize('view', $product);
        $product->load(['createdBy', 'shipments.distributorProfile']);

        return view('distribution.products.show', [
            'product' => $product,
            'admin' => $request->routeIs('admin.*') || $request->user()->isAdmin(),
        ]);
    }

    public function edit(Request $request, OilProduct $product, OilLotLookup $lots): View
    {
        $this->authorize('update', $product);

        $availableLots = collect($lots->available());
        $currentLot = $product->oilLot();
        if ($currentLot !== null && ! $availableLots->contains('id', $currentLot->id)) {
            $availableLots->prepend($currentLot);
        }

        return view('distribution.products.form', [
            'product' => $product,
            'lots' => $availableLots,
            'admin' => $request->routeIs('admin.*') || $request->user()->isAdmin(),
        ]);
    }

    public function update(UpdateOilProductRequest $request, OilProduct $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('distribution/products', 'public');
            if ($product->image !== null) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $newImage;
        }

        $product->fill($data)->save();

        return redirect()->route($request->user()->isAdmin() ? 'admin.products.show' : 'producer.products.show', $product)
            ->with('success', 'Product updated.');
    }

    public function archive(OilProduct $product): RedirectResponse
    {
        $this->authorize('archive', $product);
        abort_unless($product->archive(), 500, 'The product could not be archived.');

        return redirect()->route(request()->user()->isAdmin() ? 'admin.products.show' : 'producer.products.show', $product)
            ->with('success', 'Product archived. Its traceability history remains available.');
    }

    public function toggleVisibility(OilProduct $product): RedirectResponse
    {
        $this->authorize('toggleVisibility', $product);
        $this->changeVisibility($product);

        return back()->with('success', 'Product visibility updated.');
    }

    public function moderateVisibility(Request $request, OilProduct $product): RedirectResponse
    {
        $this->authorize('forceHide', $product);
        $data = $request->validate([
            'public_status' => ['required', Rule::enum(OilProductPublicStatus::class)],
        ]);
        abort_if($product->archived_at !== null && $data['public_status'] === OilProductPublicStatus::Visible->value, 422, 'An archived product cannot be made publicly visible.');
        $product->public_status = OilProductPublicStatus::from($data['public_status']);
        $product->save();

        return back()->with('success', 'Product public page visibility updated.');
    }

    private function changeVisibility(OilProduct $product): void
    {
        abort_if($product->archived_at !== null, 422, 'An archived product cannot be made publicly visible.');
        $product->public_status = $product->public_status === OilProductPublicStatus::Visible
            ? OilProductPublicStatus::Hidden
            : OilProductPublicStatus::Visible;
        $product->save();
    }
}
