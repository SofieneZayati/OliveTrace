<?php

namespace App\Http\Controllers\Distribution;

use App\Contracts\BatchOilLotCertificationStatusProvider;
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
                ->select(['oil_product_id', 'status', 'co2_estimate', 'destination', 'arrival_date'])
                ->orderByRaw("CASE WHEN status = 'delivered' THEN 0 ELSE 1 END")
                ->latest('arrival_date')])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')))
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
                ->orderBy('name')->orderBy('brand')->orderBy('slug'))
            ->when(($filters['sort'] ?? 'newest') === 'newest', fn (Builder $query) => $query
                ->orderByDesc('created_at')->orderBy('slug'))
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
            $deliveredShipment = $product->shipments->first();
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

        return view('distribution.catalog.index', [
            'products' => $products,
            'filters' => $filters,
            'traceRouteAvailable' => Route::has('trace.show'),
        ]);
    }

    private function formatCo2(float $co2Kg): string
    {
        $locale = app()->getLocale();
        if (class_exists(\NumberFormatter::class)) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 1);
            $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 1);

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
