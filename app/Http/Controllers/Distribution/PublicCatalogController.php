<?php

namespace App\Http\Controllers\Distribution;

use App\Contracts\BatchOilLotCertificationStatusProvider;
use App\Contracts\OilLotCertificationStatusProvider;
use App\Contracts\OilLotLookup;
use App\Contracts\ProductRatingSummary;
use App\Enums\ShipmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Distribution\ListPublicCatalogRequest;
use App\Models\Distribution\OilProduct;
use App\Services\Consumer\PublicProductOrigins;
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
        PublicProductOrigins $origins,
    ): View {
        $filters = $request->validated();
        $products = $origins->join(OilProduct::query()->publiclyVisible())
            ->select(array_map(fn ($column) => 'oil_products.'.$column, ['id', 'oil_lot_id', 'name', 'brand', 'description', 'bottle_volume_ml', 'packaging_date', 'created_at', 'image', 'slug']))
            ->addSelect($origins->columns())
            ->with(['shipments' => fn (HasMany $query) => $query
                ->select(['oil_product_id', 'status', 'co2_estimate', 'destination', 'arrival_date'])
                ->orderByRaw("CASE WHEN status = 'delivered' THEN 0 ELSE 1 END")
                ->latest('arrival_date')])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where(fn (Builder $query) => $query
                    ->where('oil_products.name', 'like', '%'.$search.'%')
                    ->orWhere('oil_products.brand', 'like', '%'.$search.'%')
                    ->orWhere(fn ($origin) => $origin->whereNotNull('origin_users.id')
                        ->where(fn ($fields) => $fields->where('origin_farms.name', 'like', '%'.$search.'%')
                            ->orWhere('origin_farms.olive_variety', 'like', '%'.$search.'%')
                            ->orWhere('origin_profiles.display_name', 'like', '%'.$search.'%')))))
            ->when($filters['region'] ?? null, fn (Builder $query, string $region) => $query
                ->whereNotNull('origin_users.id')->where('origin_farms.governorate', $region))
            ->when($filters['bottle_volume_ml'] ?? null, fn (Builder $query, string $volume) => $query
                ->where('bottle_volume_ml', (int) $volume))
            ->when(($filters['delivered'] ?? null) === '1', fn (Builder $query) => $query
                ->whereHas('shipments', fn (Builder $shipments) => $shipments->where('status', 'delivered')))
            ->when(($filters['sort'] ?? 'newest') === 'lowest_co2', fn (Builder $query) => $query
                ->withSum(['shipments as transport_co2_sort' => fn (Builder $shipments) => $shipments
                    ->where('status', '!=', 'cancelled')
                    ->whereNotNull('co2_estimate')], 'co2_estimate')
                ->orderByRaw('transport_co2_sort IS NULL')
                ->orderBy('transport_co2_sort')
                ->orderBy('slug'))
            ->when(($filters['sort'] ?? 'newest') === 'name', fn (Builder $query) => $query
                ->orderBy('oil_products.name')->orderBy('oil_products.brand')->orderBy('oil_products.slug'))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn (Builder $query) => $query
                ->orderByDesc('oil_products.created_at')->orderBy('oil_products.slug'))
            ->paginate(12)
            ->withQueryString();

        $lotSummaries = $lots->findMany($products->getCollection()->pluck('oil_lot_id')->unique()->values()->all());
        $certificationStatuses = $certifications instanceof BatchOilLotCertificationStatusProvider
            ? collect($certifications->statusesFor(array_keys($lotSummaries)))
            : collect($lotSummaries)->mapWithKeys(fn ($lot): array => [
                $lot->id => strtolower(trim($certifications->statusFor($lot->id))),
            ]);
        $catalogCards = $products->getCollection()->map(function (OilProduct $product) use ($lotSummaries, $certificationStatuses, $ratings): array {
            $lot = $lotSummaries[$product->oil_lot_id] ?? null;
            $deliveredShipment = $product->shipments->first(fn ($shipment) => $shipment->status === ShipmentStatus::Delivered);
            $transportCo2Kg = $product->transportCo2KgIfAvailable();

            return [
                'product' => $product,
                'lot' => $lot,
                'certificationStatus' => $lot === null ? 'not available' : ($certificationStatuses[$lot->id] ?? 'not available'),
                'rating' => $ratings->summary((int) $product->getKey()),
                'deliveredDestination' => $deliveredShipment?->destination,
                'transportCo2Label' => $transportCo2Kg === null ? null : $this->formatCo2($transportCo2Kg),
                'imageUrl' => $product->image !== null && Storage::disk('public')->exists($product->image)
                    ? Storage::disk('public')->url($product->image)
                    : null,
            ];
        });

        $products->setCollection($catalogCards);

        $originPoints = $catalogCards->pluck('product')->filter(fn ($product) => $product->origin_region)
            ->groupBy('origin_region')->map(fn ($items, $region) => $origins->point($region,
                route('catalog.index', [...$filters, 'region' => $region]), $items->count()))->filter()->values()->all();

        return view('distribution.catalog.index', [
            'products' => $products,
            'filters' => $filters,
            'traceRouteAvailable' => Route::has('trace.show'),
            'originPoints' => $originPoints,
        ]);
    }

    private function formatCo2(float $co2Kg): string
    {
        $locale = app()->getLocale();
        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 1);
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 1);
            $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);

            return $formatter->format($co2Kg);
        }

        $language = strtolower(substr(str_replace('_', '-', $locale), 0, 2));
        $commaDecimal = in_array($language, ['de', 'es', 'fi', 'fr', 'it', 'nl', 'no', 'pl', 'pt', 'ru', 'sv', 'tr'], true);
        $decimalSeparator = $commaDecimal ? ',' : '.';
        $thousandsSeparator = match ($language) {
            'de', 'es', 'it' => '.',
            'fr', 'ru' => "\u{202F}",
            'fi', 'nl', 'no', 'pl', 'pt', 'sv', 'tr' => "\u{00A0}",
            default => ',',
        };

        return number_format($co2Kg, 1, $decimalSeparator, $thousandsSeparator);
    }
}
