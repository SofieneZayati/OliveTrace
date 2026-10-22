<?php

namespace App\Services\Consumer;

use App\Models\Consumer\Complaint;
use App\Models\User;
use Carbon\CarbonInterval;
use Illuminate\Support\Collection;

class SlaDashboardService
{
    public function getMetrics(?CarbonInterval $period = null): array
    {
        $query = Complaint::query();

        if ($period) {
            $query->where('created_at', '>=', now()->sub($period));
        }

        $all = $query->get();
        $resolved = $all->where('status', 'resolved');
        $overdue = $all->filter(fn ($c) => $c->isOverdue());

        $total = $all->count();
        $resolvedCount = $resolved->count();

        return [
            'total_complaints' => $total,
            'open_complaints' => $all->where('status', 'open')->count(),
            'in_review_complaints' => $all->where('status', 'in_review')->count(),
            'resolved_complaints' => $resolvedCount,
            'rejected_complaints' => $all->where('status', 'rejected')->count(),
            'overdue_complaints' => $overdue->count(),
            'reopened_complaints' => $all->where('reopen_count', '>', 0)->count(),
            'resolution_rate' => $total > 0 ? round(($resolvedCount / $total) * 100, 1) : 0,
            'avg_resolution_time_hours' => $this->avgResolutionTime($resolved),
            'median_resolution_time_hours' => $this->medianResolutionTime($resolved),
            'resolved_under_48h_pct' => $this->resolvedUnder48hPct($resolved),
            'avg_reopens_per_complaint' => $total > 0 ? round($all->avg('reopen_count'), 2) : 0,
            'by_priority' => $this->byPriority($all),
            'by_category' => $this->byCategory($all),
            'by_admin' => $this->byAdmin($resolved),
            'trend' => $this->trend($period),
        ];
    }

    public function getAdminWorkload(): Collection
    {
        return User::whereHas('complaintsAdmin', function ($q) {
            $q->whereIn('status', ['open', 'in_review']);
        })->withCount([
            'complaintsAdmin as open_count' => fn ($q) => $q->where('status', 'open'),
            'complaintsAdmin as in_review_count' => fn ($q) => $q->where('status', 'in_review'),
            'complaintsAdmin as overdue_count' => fn ($q) => $q->where(function ($q) {
                $q->where('sla_deadline', '<', now())
                    ->whereNotIn('status', ['resolved', 'rejected']);
            }),
        ])->get()->map(function ($admin) {
            return [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'open' => $admin->open_count,
                'in_review' => $admin->in_review_count,
                'overdue' => $admin->overdue_count,
                'total_active' => $admin->open_count + $admin->in_review_count,
            ];
        })->sortByDesc('total_active');
    }

    private function avgResolutionTime(Collection $resolved): ?float
    {
        $times = $resolved
            ->filter(fn ($c) => $c->resolved_at && $c->created_at)
            ->map(fn ($c) => $c->created_at->diffInHours($c->resolved_at))
            ->filter(fn ($h) => $h > 0);

        return $times->isNotEmpty() ? round($times->avg(), 1) : null;
    }

    private function medianResolutionTime(Collection $resolved): ?float
    {
        $times = $resolved
            ->filter(fn ($c) => $c->resolved_at && $c->created_at)
            ->map(fn ($c) => $c->created_at->diffInHours($c->resolved_at))
            ->filter(fn ($h) => $h > 0)
            ->sort();

        if ($times->isEmpty()) {
            return null;
        }

        $count = $times->count();
        $middle = (int) ($count / 2);

        return $count % 2 === 0
            ? round(($times[$middle - 1] + $times[$middle]) / 2, 1)
            : round($times[$middle], 1);
    }

    private function resolvedUnder48hPct(Collection $resolved): ?float
    {
        $withTime = $resolved->filter(fn ($c) => $c->resolved_at && $c->created_at);
        if ($withTime->isEmpty()) {
            return null;
        }

        $under48 = $withTime->filter(fn ($c) => $c->created_at->diffInHours($c->resolved_at) <= 48)->count();

        return round(($under48 / $withTime->count()) * 100, 1);
    }

    private function byPriority(Collection $all): array
    {
        return $all->groupBy('ai_priority')
            ->map(fn ($group) => $group->count())
            ->sortKeys()
            ->toArray();
    }

    private function byCategory(Collection $all): array
    {
        return $all->groupBy('ai_category')
            ->map(fn ($group) => $group->count())
            ->sortDesc()
            ->toArray();
    }

    private function byAdmin(Collection $resolved): array
    {
        // This would need a relationship to track which admin resolved
        // For now, return placeholder
        return [];
    }

    private function trend(?CarbonInterval $period): array
    {
        $days = $period ? $period->days : 30;
        $start = now()->subDays($days);

        $daily = Complaint::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(fn ($item) => $item->count);

        $labels = [];
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');
            $data[] = $daily[$date] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }
}
