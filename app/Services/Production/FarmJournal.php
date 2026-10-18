<?php

namespace App\Services\Production;

use App\Enums\FarmActivityStatus;
use App\Enums\FarmActivityType;
use App\Enums\FarmStatus;
use App\Enums\Role;
use App\Models\Production\Farm;
use App\Models\Production\FarmActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FarmJournal
{
    public function authorizeWrite(User $actor, Farm $farm): void
    {
        Gate::forUser($actor)->authorize('view', $farm);
        abort_unless($actor->role === Role::Producer && $farm->producerProfile->user_id === $actor->id, 403);
        if ($farm->status !== FarmStatus::Active || ! $farm->producerProfile->is_active) {
            throw ValidationException::withMessages(['farm' => 'This farm is read-only. Its farm and producer profile must be active to record work.']);
        }
    }

    public function create(User $actor, Farm $farm, array $data): FarmActivity
    {
        return DB::transaction(fn () => $this->currentWritableFarm($actor, $farm)->activities()->create($data));
    }

    public function update(User $actor, Farm $farm, int $activityId, array $data): FarmActivity
    {
        return DB::transaction(function () use ($actor, $farm, $activityId, $data) {
            $current = $this->currentWritableFarm($actor, $farm);
            $activity = $current->activities()->lockForUpdate()->findOrFail($activityId);
            $this->requirePlanned($activity);
            $activity->update($data);

            return $activity;
        });
    }

    public function transition(User $actor, Farm $farm, int $activityId, array $data): void
    {
        DB::transaction(function () use ($actor, $farm, $activityId, $data) {
            $current = $this->currentWritableFarm($actor, $farm);
            $activity = $current->activities()->lockForUpdate()->findOrFail($activityId);
            $this->requirePlanned($activity);
            $completing = ($data['status'] ?? null) === FarmActivityStatus::Completed->value;
            $irrigating = $activity->type === FarmActivityType::Irrigation;
            $validated = Validator::make($data, [
                'status' => ['required', Rule::in(['completed', 'cancelled'])],
                'completed_on' => [$completing ? 'required' : 'prohibited', 'nullable', 'date_format:Y-m-d', 'after_or_equal:'.$activity->planned_on->toDateString(), 'before_or_equal:today'],
                'water_m3' => [$completing && $irrigating ? 'required' : 'prohibited', 'nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
            ])->validate();
            $activity->status = FarmActivityStatus::from($validated['status']);
            $activity->completed_on = $completing ? $validated['completed_on'] : null;
            $activity->water_m3 = $completing && $irrigating ? $validated['water_m3'] : null;
            $activity->save();
        });
    }

    private function currentWritableFarm(User $actor, Farm $farm): Farm
    {
        $current = Farm::with('producerProfile')->lockForUpdate()->findOrFail($farm->id);
        $this->authorizeWrite($actor, $current);

        return $current;
    }

    private function requirePlanned(FarmActivity $activity): void
    {
        if ($activity->status !== FarmActivityStatus::Planned) {
            throw ValidationException::withMessages(['activity' => 'Completed and cancelled activities are retained as history and cannot be edited or reopened.']);
        }
    }
}
