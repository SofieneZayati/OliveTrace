<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Services\Production\PublicOrigin;

class OriginController extends Controller
{
    public function show(int $farm, PublicOrigin $origins)
    {
        $origin = $origins->forFarm($farm) ?? abort(404);

        return response()->view('production.origin', compact('origin'))
            ->header('Cache-Control', 'no-store');
    }
}
