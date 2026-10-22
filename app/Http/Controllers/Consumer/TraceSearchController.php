<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Models\Distribution\OilProduct;
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
        $query = trim($request->input('q', ''));

        if (empty($query)) {
            return redirect()->route('trace.search')->with('error', 'Veuillez entrer un code produit ou un numéro de lot.');
        }

        if (! Schema::hasTable('oil_products')) {
            return redirect()->route('trace.search')->with('error', 'Les produits ne sont pas encore disponibles.');
        }

        // Try exact slug match first
        $product = OilProduct::findBySlug($query);

        // Try by numeric ID
        if (! $product && ctype_digit($query)) {
            $product = OilProduct::find((int) $query);
        }

        // Try partial name/brand search
        if (! $product) {
            $product = OilProduct::where('name', 'like', "%{$query}%")
                ->orWhere('brand', 'like', "%{$query}%")
                ->first();
        }

        // Try by lot code through oil_lots relation
        if (! $product && Schema::hasTable('oil_lots')) {
            $lotId = \Illuminate\Support\Facades\DB::table('oil_lots')
                ->where('lot_number', 'like', "%{$query}%")
                ->value('id');
            if ($lotId) {
                $product = OilProduct::where('oil_lot_id', $lotId)->first();
            }
        }

        if ($product && $product->isPubliclyVisible()) {
            return redirect()->route('trace.show', $product->slug ?? $product->id);
        }

        return redirect()->route('trace.search')
            ->with('error', 'Aucun produit trouvé pour "' . e($query) . '". Vérifiez le code ou contactez le vendeur.')
            ->withInput();
    }
}
