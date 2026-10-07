<?php

namespace App\Services\Distribution;

use App\Models\Distribution\OilProduct;
use Illuminate\Support\Facades\Route;

class ProductTraceUrl
{
    public function forProduct(OilProduct $product): string
    {
        if (Route::has('trace.show')) {
            return route('trace.show', $product->slug);
        }

        $path = trim((string) config('olivetrace-distribution.trace_path', '/trace'), '/');

        return url(($path === '' ? '' : '/'.$path).'/'.rawurlencode((string) $product->slug));
    }
}
