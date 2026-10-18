<?php

namespace App\Services\Production;

use App\Enums\FarmActivityStatus;
use App\Enums\FarmActivityType;
use App\Models\Production\Farm;
use App\Models\Production\FarmActivity;

class FarmAdviceContext
{
    public function snapshot(Farm $farm): array
    {
        $from = today()->subDays(90)->toDateString();
        $to = today()->addDays(30)->toDateString();
        $query = $farm->activities()->whereBetween('planned_on', [$from, $to]);
        $total = (clone $query)->count();
        $entries = $query->orderByDesc('planned_on')->orderByDesc('id')->limit(30)->get();

        return [
            'as_of' => today()->toDateString(),
            'window_start' => $from, 'window_end' => $to,
            'matching_records' => $total, 'included_records' => $entries->count(),
            'selection' => 'Up to 30 entries, ordered by planned date descending. Totals below cover only these entries; missing records do not prove that work was not performed.',
            'completed_irrigation_water_m3' => number_format((float) $entries
                ->filter(fn (FarmActivity $entry) => $entry->status === FarmActivityStatus::Completed && $entry->type === FarmActivityType::Irrigation)->sum('water_m3'), 2, '.', ''),
            'overdue_plans' => $entries->filter(fn (FarmActivity $entry) => $entry->isOverdue())->count(),
            'activities' => $entries->map(fn (FarmActivity $entry) => [
                'id' => $entry->id, 'type' => $entry->type->value, 'status' => $entry->status->value,
                'planned_on' => $entry->planned_on->toDateString(),
                'completed_on' => $entry->completed_on?->toDateString(), 'water_m3' => $entry->water_m3,
            ])->all(),
        ];
    }
}
