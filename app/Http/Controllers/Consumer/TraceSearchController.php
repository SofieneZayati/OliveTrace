<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Distribution\OilProduct;
use App\Models\Production\OilLot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

// Module 5 (Aymen) — Trace search and QR scanner controller.
// Provides a public entry point for consumers to find a product trace
// by scanning a QR code or typing a slug, lot code, or product name.
class TraceSearchController extends Controller
{
    public function index()
    {
        return view('consumer.trace.search');
    }

    public function search(Request $request)
    {
        $data = $request->validate(['q' => ['required', 'string', 'max:160']]);
        $query = trim($data['q']);

        if (! Schema::hasTable('oil_products')) {
            return redirect()->route('trace.search')->with('error', 'Les produits ne sont pas encore disponibles.');
        }

        // Try exact slug match first
        $product = OilProduct::publiclyVisible()->where('slug', $query)->first();

        // Try by numeric ID
        if (! $product && ctype_digit($query)) {
            $product = OilProduct::publiclyVisible()->find((int) $query);
        }

        // Try partial name/brand search
        if (! $product) {
            $product = OilProduct::publiclyVisible()
                ->where(fn ($products) => $products->where('name', 'like', "%{$query}%")
                    ->orWhere('brand', 'like', "%{$query}%"))
                ->first();
        }

        // Try by lot code through oil_lots relation
        if (! $product && Schema::hasTable('oil_lots')) {
            $product = OilProduct::publiclyVisible()->whereIn('oil_lot_id',
                OilLot::query()->select('id')->where('lot_number', 'like', "%{$query}%")
            )->first();
        }

        if ($product && $product->isPubliclyVisible()) {
            return redirect()->route('trace.show', $product->slug ?? $product->id);
        }

        return redirect()->route('trace.search')
            ->with('error', 'Aucun produit trouvé pour "'.$query.'". Vérifiez le code ou contactez le vendeur.')
            ->withInput();
    }
}
