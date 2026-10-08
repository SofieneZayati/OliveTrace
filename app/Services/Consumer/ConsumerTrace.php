<?php

namespace App\Services\Consumer;

use App\Models\Consumer\Feedback;
use App\Models\Consumer\OilProduct;
use App\Models\Production\Farm;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

// Builds the public trace page by reading every upstream module through
// relations only — Module 5 never duplicates farm, lot, certificate or
// shipment data. Each section degrades to "not available" when the owning
// module has not landed yet, so the page never crashes on partial data.
class ConsumerTrace
{
    public function forSlug(string $slug): ?array
    {
        if (! Schema::hasTable('oil_products')) {
            return null;
        }

        $product = $this->findProduct($slug);
        if (! $product) {
            return null;
        }

        $oilLot = $this->oilLot($product);
        $harvest = $this->harvest($oilLot);
        $farm = $this->farm($harvest);

        return [
            'product' => $product,
            'image_url' => $this->imageUrl($product),
            'origin' => $this->origin($farm),
            'harvest' => $harvest,
            'mill' => $this->mill($oilLot),
            'oil_lot' => $oilLot,
            'verification' => $this->verification($oilLot),
            'shipments' => $this->shipments($product),
            'co2_total_kg' => $this->co2Total($product),
            'feedback' => $this->feedback($product),
        ];
    }

    private function findProduct(string $slug): ?OilProduct
    {
        $query = OilProduct::query();

        $columns = Schema::getColumnListing('oil_products');
        if (in_array('qr_token', $columns, true)) {
            $query->where('qr_token', $slug);
        }
        if (in_array('slug', $columns, true)) {
            $query->orWhere('slug', $slug);
        }
        if (ctype_digit($slug)) {
            $query->orWhere('id', (int) $slug);
        } elseif (! in_array('qr_token', $columns, true) && ! in_array('slug', $columns, true)) {
            return null;
        }

        $product = $query->first();

        // A product hidden or archived by the distribution module must not
        // resolve to a public trace page.
        if ($product && in_array('public_status', $columns, true) && (string) $product->public_status === 'hidden') {
            return null;
        }
        if ($product && in_array('archived_at', $columns, true) && $product->archived_at !== null) {
            return null;
        }

        return $product;
    }

    private function tableRow(string $table, string $column, mixed $value): ?object
    {
        if ($value === null || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return null;
        }

        return \DB::table($table)->where('id', $value)->first();
    }

    private function oilLot(OilProduct $product): ?object
    {
        if (! isset($product->oil_lot_id)) {
            return null;
        }

        return $this->tableRow('oil_lots', 'id', $product->oil_lot_id);
    }

    private function harvest(?object $oilLot): ?object
    {
        return $oilLot && isset($oilLot->harvest_id)
            ? $this->tableRow('harvests', 'id', $oilLot->harvest_id)
            : null;
    }

    private function farm(?object $harvest): ?Farm
    {
        if (! $harvest || ! isset($harvest->farm_id)) {
            return null;
        }

        return Farm::with('producerProfile')->find($harvest->farm_id);
    }

    private function mill(?object $oilLot): ?object
    {
        if (! $oilLot || ! isset($oilLot->mill_request_id)) {
            return null;
        }

        $request = $this->tableRow('mill_requests', 'id', $oilLot->mill_request_id);
        if (! $request || ! isset($request->mill_id)) {
            return $request ? (object) ['external' => $request->external_mill_name ?? null, 'request' => $request] : null;
        }

        $mill = $this->tableRow('mills', 'id', $request->mill_id);

        return $mill ? (object) ['mill' => $mill, 'request' => $request] : (object) ['external' => null, 'request' => $request];
    }

    private function origin(?Farm $farm): ?array
    {
        if (! $farm || ! $farm->is_public || ! $farm->producerProfile || ! $farm->producerProfile->is_public) {
            return null;
        }

        return $farm->publicOrigin();
    }

    private function verification(?object $oilLot): ?array
    {
        if (! $oilLot || ! Schema::hasTable('certificate_requests')) {
            return null;
        }

        $request = \DB::table('certificate_requests')->where('oil_lot_id', $oilLot->id)->latest('id')->first();
        if (! $request) {
            return null;
        }

        $certificate = Schema::hasTable('certificates')
            ? \DB::table('certificates')->where('certificate_request_id', $request->id)->latest('id')->first()
            : null;

        $expired = $certificate && isset($certificate->expiry_date) && $certificate->expiry_date < now()->toDateString();

        return ['request' => $request, 'certificate' => $certificate, 'expired' => $expired];
    }

    /** @return list<object> */
    private function shipments(OilProduct $product): array
    {
        if (! Schema::hasTable('shipments')) {
            return [];
        }

        return \DB::table('shipments')->where('oil_product_id', $product->id)->orderBy('departure_date')->get()->all();
    }

    // Read-only environmental summary: total transport CO2 estimated by the
    // distribution module (cancelled legs excluded). Displayed, never edited.
    private function co2Total(OilProduct $product): ?float
    {
        if (! Schema::hasTable('shipments') || ! Schema::hasColumn('shipments', 'co2_estimate')) {
            return null;
        }

        $total = \DB::table('shipments')->where('oil_product_id', $product->id)
            ->where('status', '!=', 'cancelled')->whereNotNull('co2_estimate')->sum('co2_estimate');

        return $total > 0 ? (float) $total : null;
    }

    private function imageUrl(OilProduct $product): ?string
    {
        if (empty($product->image)) {
            return null;
        }

        return Storage::disk('public')->exists($product->image) ? Storage::disk('public')->url($product->image) : null;
    }

    private function feedback(OilProduct $product): array
    {
        $items = Feedback::with('consumer')->where('oil_product_id', $product->id)->visible()->latest()->get();

        return [
            'items' => $items,
            'average' => $items->isNotEmpty() ? round($items->avg('rating'), 1) : null,
            'count' => $items->count(),
        ];
    }
}
