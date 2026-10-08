<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Http\Requests\Production\MillRequestRequest;
use App\Models\Production\Harvest;
use App\Models\Production\MillRequest;
use App\Services\Production\HarvestManagement;
use Illuminate\Http\Request;

class MillRequestController extends Controller
{
    public function store(MillRequestRequest $request, HarvestManagement $management, int $harvest)
    {
        $record = Harvest::findOrFail($harvest);
        $management->sendMillRequest($request->user(), $record, $request->validated());

        return redirect()->route('producer.harvests.show', $record->id)
            ->with('success', 'Your request was sent to the mill. Follow its status on this page.');
    }

    public function cancel(Request $request, HarvestManagement $management, int $mill_request)
    {
        $record = MillRequest::findOrFail($mill_request);
        $management->cancelMillRequest($request->user(), $record);

        return redirect()->route('producer.harvests.show', $record->harvest_id)
            ->with('success', 'The request was cancelled and its quantity is available again.');
    }
}
