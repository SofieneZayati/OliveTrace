<?php

namespace App\Services\Production;

use App\Enums\FarmStatus;
use App\Enums\HarvestMethod;
use App\Enums\HarvestStatus;
use App\Enums\MillRequestStatus;
use App\Models\Production\Farm;
use App\Models\Production\Harvest;
use App\Models\Production\MillRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class HarvestManagement
{
    public function createHarvest(User $actor, array $data): Harvest
    {
        $farm = $this->selectableFarm($actor, (int) $data['farm_id']);
        $harvest = new Harvest;
        $harvest->farm()->associate($farm);

        return $this->saveHarvest($harvest, $data);
    }

    public function updateHarvest(User $actor, Harvest $harvest, array $data): Harvest
    {
        Gate::forUser($actor)->authorize('update', $harvest);
        $harvest->farm()->associate($this->selectableFarm($actor, (int) $data['farm_id'], $harvest->farm_id));

        return $this->saveHarvest($harvest, $data);
    }

    private function saveHarvest(Harvest $harvest, array $data): Harvest
    {
        $harvest->harvest_date = $data['harvest_date'];
        $harvest->expected_end_date = $data['expected_end_date'] ?: null;
        $harvest->method = HarvestMethod::from($data['method']);
        $harvest->quantity_kg = isset($data['quantity_kg']) && $data['quantity_kg'] !== '' && $data['quantity_kg'] !== null
            ? number_format((float) $data['quantity_kg'], 2, '.', '')
            : null;
        $harvest->status = HarvestStatus::from($data['status']);
        $harvest->notes = $data['notes'] ?: null;
        $harvest->save();

        return $harvest;
    }

    public function deleteHarvest(User $actor, Harvest $harvest): void
    {
        Gate::forUser($actor)->authorize('delete', $harvest);
        DB::transaction(function () use ($harvest) {
            $record = Harvest::whereKey($harvest->id)->lockForUpdate()->firstOrFail();
            if ($record->millRequests()->exists()) {
                throw ValidationException::withMessages(['harvest' => 'This harvest has mill requests and must be kept as history.']);
            }
            $record->delete();
        });
    }

    public function sendMillRequest(User $actor, Harvest $harvest, array $data): MillRequest
    {
        Gate::forUser($actor)->authorize('create', [MillRequest::class, $harvest]);
        if ($harvest->status === HarvestStatus::Milled) {
            throw ValidationException::withMessages(['harvest' => 'This harvest was already milled.']);
        }
        if ($harvest->millRequests()->whereIn('status', [MillRequestStatus::Pending, MillRequestStatus::Accepted])->exists()) {
            throw ValidationException::withMessages(['mill_id' => 'This harvest already has a mill request waiting for an answer.']);
        }
        $quantity = (float) $data['quantity_kg'];
        if ($harvest->quantity_kg !== null) {
            if (round($harvest->requestedQuantity() + $quantity, 2) > round((float) $harvest->quantity_kg, 2)) {
                throw ValidationException::withMessages(['quantity_kg' => 'Only '.$harvest->remainingQuantity().' kg are still available on this harvest.']);
            }
        }

        $request = new MillRequest;
        $request->harvest()->associate($harvest);
        $request->mill_id = $data['mill_id'] ?: null;
        $request->external_mill_name = $data['mill_id'] ? null : ($data['external_mill_name'] ?: null);
        $request->requested_date = $data['requested_date'];
        $request->quantity_kg = number_format($quantity, 2, '.', '');
        $request->message = $data['message'] ?: null;
        $request->status = MillRequestStatus::Pending;
        $request->save();

        return $request;
    }

    public function cancelMillRequest(User $actor, MillRequest $request): void
    {
        Gate::forUser($actor)->authorize('cancel', $request);
        $request->status = MillRequestStatus::Cancelled;
        $request->save();
    }

    private function selectableFarm(User $actor, int $farmId, ?int $currentFarmId = null): Farm
    {
        $farm = Farm::find($farmId);
        if (! $farm || ! Gate::forUser($actor)->allows('update', $farm)) {
            throw ValidationException::withMessages(['farm_id' => 'Choose one of your farms.']);
        }
        if ($farm->id !== $currentFarmId && $farm->status !== FarmStatus::Active) {
            throw ValidationException::withMessages(['farm_id' => 'Choose a farm that is still active.']);
        }

        return $farm;
    }
}
