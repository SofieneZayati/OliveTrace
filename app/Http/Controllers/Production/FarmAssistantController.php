<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Repositories\Production\Farms;
use App\Services\Production\FarmSustainabilityAssistant;
use App\Services\Production\PublicOrigin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FarmAssistantController extends Controller
{
    public function __invoke(Request $request, int $farm, Farms $farms, FarmSustainabilityAssistant $assistant)
    {
        $record = $farms->find($farm);
        Gate::authorize('view', $record);
        $result = $assistant->advise($record);

        // Render this response directly: generated advice is ephemeral, not stored in farm records or session logs.
        return response()->view('production.farms.show', [
            'farm' => $record, 'admin' => $request->routeIs('admin.*'), 'assistantResult' => $result,
            'publicOrigin' => app(PublicOrigin::class)->forFarm($record->id),
        ])->header('Cache-Control', 'no-store');
    }
}
