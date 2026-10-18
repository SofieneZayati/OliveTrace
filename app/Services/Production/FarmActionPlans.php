<?php

namespace App\Services\Production;

use App\Enums\FarmStatus;
use App\Models\Production\Farm;
use App\Models\Production\FarmActivity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FarmActionPlans
{
    public function generate(User $actor, Farm $farm, FarmSustainabilityAssistant $assistant): array
    {
        $this->authorizeGeneration($actor, $farm);
        $result = $assistant->advise($farm, actionPlan: true);
        if (! $result['available']) {
            return $result;
        }
        $report = DB::transaction(function () use ($actor, $farm, $result) {
            // The network request runs outside the transaction. Recheck the farm before saving.
            $current = Farm::with('producerProfile')->lockForUpdate()->findOrFail($farm->id);
            $this->authorizeGeneration($actor->fresh(), $current);

            return $current->adviceReports()->create([
                'provider' => $result['provider'], 'model' => $result['model'],
                'facts' => $result['facts'], 'advice' => $result['advice'],
            ]);
        });

        return ['available' => true, 'report' => $report];
    }

    public function adopt(User $actor, Farm $farm, int $reportId, int $index, array $data): FarmActivity
    {
        return DB::transaction(function () use ($actor, $farm, $reportId, $index, $data) {
            $current = Farm::with('producerProfile')->lockForUpdate()->findOrFail($farm->id);
            app(FarmJournal::class)->authorizeWrite($actor, $current);
            $report = $current->adviceReports()->lockForUpdate()->findOrFail($reportId);
            $recommendation = $report->advice['recommendations'][$index] ?? null;
            abort_unless(is_array($recommendation), 404);
            $validated = Validator::make($data, [
                'reviewed' => ['required', 'accepted'],
                'title' => ['required', 'string', 'max:150'],
                'planned_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addYear()->toDateString()],
                'notes' => ['nullable', 'string', 'max:2000'],
            ])->validate();
            // Lock plus a unique index make repeat clicks safe, including after cancellation.
            $existing = $report->activities()->where('recommendation_index', $index)->first();
            if ($existing) {
                return $existing;
            }
            $activity = new FarmActivity;
            $activity->fill([
                'title' => $validated['title'], 'type' => $recommendation['type'],
                'planned_on' => $validated['planned_on'], 'notes' => $validated['notes'] ?? null,
            ]);
            $activity->farm()->associate($current);
            $activity->adviceReport()->associate($report);
            $activity->recommendation_index = $index;
            $activity->save();

            return $activity;
        });
    }

    private function authorizeGeneration(User $actor, Farm $farm): void
    {
        Gate::forUser($actor)->authorize('view', $farm);
        if ($farm->status !== FarmStatus::Active || ! $farm->producerProfile->is_active) {
            throw ValidationException::withMessages(['farm' => 'AI plans can only be generated for an active farm and producer profile. Saved reports remain readable.']);
        }
    }
}
