<?php

namespace App\Http\Controllers\Distribution;

use App\Contracts\OilLotCertificationStatusProvider;
use App\Contracts\OilLotLookup;
use App\Contracts\ProductRatingSummary;
use App\Http\Controllers\Controller;
use App\Http\Requests\Distribution\ListPublicCatalogRequest;
use App\Models\Distribution\OilProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicCatalogController extends Controller
{
    public function __invoke(
        ListPublicCatalogRequest $request,
        OilLotLookup $lots,
        OilLotCertificationStatusProvider $certifications,
        ProductRatingSummary $ratings,
    ): View {
        $filters = $request->validated();
        $products = OilProduct::query()
            ->publiclyVisible()
            ->select(['id', 'oil_lot_id', 'name', 'brand', 'bottle_volume_ml', 'packaging_date', 'created_at', 'image', 'slug'])
            ->with(['shipments' => fn (HasMany $query) => $query
                ->where('status', 'delivered')
                ->select(['oil_product_id', 'destination', 'arrival_date'])
                ->latest('arrival_date')])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')))
            ->when($filters['bottle_volume_ml'] ?? null, fn (Builder $query, string $volume) => $query
                ->where('bottle_volume_ml', (int) $volume))
            ->when(($filters['delivered'] ?? null) === '1', fn (Builder $query) => $query
                ->whereHas('shipments', fn (Builder $shipments) => $shipments->where('status', 'delivered')))
            ->when(($filters['sort'] ?? 'newest') === 'name', fn (Builder $query) => $query
                ->orderBy('name')->orderBy('brand')->orderBy('slug'))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn (Builder $query) => $query
                ->orderByDesc('created_at')->orderBy('slug'))
            ->paginate(12)
            ->withQueryString();

        $lotSummaries = $lots->findMany($products->getCollection()->pluck('oil_lot_id')->unique()->values()->all());
        $certificationStatuses = collect($lotSummaries)->mapWithKeys(fn ($lot): array => [
            $lot->id => strtolower(trim($certifications->statusFor($lot->id))),
        ]);
        $catalogCards = $products->getCollection()->map(function (OilProduct $product) use ($lotSummaries, $certificationStatuses, $ratings): array {
            $lot = $lotSummaries[$product->oil_lot_id] ?? null;
            $deliveredShipment = $product->shipments->first();

            return [
                'product' => $product,
                'lot' => $lot,
                'certificationStatus' => $lot === null ? 'not available' : ($certificationStatuses[$lot->id] ?? 'not available'),
                'rating' => $ratings->summary((int) $product->getKey()),
                'deliveredDestination' => $deliveredShipment?->destination,
                'imageUrl' => $product->image !== null && Storage::disk('public')->exists($product->image)
                    ? Storage::disk('public')->url($product->image)
                    : null,
            ];
        });

        $products->setCollection($catalogCards);

        return view('distribution.catalog.index', [
            'products' => $products,
            'filters' => $filters,
            'traceRouteAvailable' => Route::has('trace.show'),
        ]);
    }
}
