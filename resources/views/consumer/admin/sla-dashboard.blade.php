@extends('layouts.admin')
@section('title', 'Tableau de bord SLA')
@section('content')
    <div class="mb-8">
        <p class="eyebrow mb-4 text-olive-600">Pilotage modération</p>
        <h1 class="display-title text-4xl">Tableau de bord SLA Réclamations</h1>
        <p class="mt-4 text-sm text-stone-500">Suivez les performances de traitement des réclamations.</p>
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" class="flex items-center gap-3">
            <label for="period" class="text-sm font-medium text-stone-600">Période :</label>
            <select id="period" name="period" onchange="this.form.submit()" class="ml-2 rounded-lg border-stone-300">
                <option value="7d" {{ request('period') === '7d' ? 'selected' : '' }}>7 jours</option>
                <option value="30d" {{ request('period') === '30d' ? 'selected' : '' }}>30 jours</option>
                <option value="90d" {{ request('period') === '90d' ? 'selected' : '' }}>90 jours</option>
            </select>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 mb-10">
        <x-card class="p-5"><dt class="eyebrow text-olive-600">Total réclamations</dt><dd class="mt-2 font-display text-3xl">{{ $metrics['total_complaints'] }}</dd></x-card>
        <x-card class="p-5"><dt class="eyebrow text-olive-600">Résolues</dt><dd class="mt-2 font-display text-3xl text-olive-600">{{ $metrics['resolved_complaints'] }}</dd></x-card>
        <x-card class="p-5"><dt class="eyebrow text-olive-600">Taux résolution</dt><dd class="mt-2 font-display text-3xl">{{ $metrics['resolution_rate'] }}%</dd></x-card>
        <x-card class="p-5"><dt class="eyebrow text-olive-600">Temps médian</dt><dd class="mt-2 font-display text-3xl">{{ $metrics['median_resolution_time_hours'] ?? '—' }} h</dd></x-card>
        <x-card class="p-5"><dt class="eyebrow text-olive-600">Résolues < 48h</dt><dd class="mt-2 font-display text-3xl">{{ $metrics['resolved_under_48h_pct'] ?? '—' }}%</dd></x-card>
    </div>

    <div class="grid gap-8 lg:grid-cols-2 mb-10">
        <x-card>
            <p class="eyebrow mb-4 text-olive-600">Répartition par priorité</p>
            @if(!empty($metrics['by_priority']))
                <dl class="grid gap-4 sm:grid-cols-2">
                    @foreach($metrics['by_priority'] as $priority => $count)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-stone-50">
                            <span class="font-medium">{{ ucfirst($priority) }}</span>
                            <span class="font-display text-2xl">{{ $count }}</span>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="text-sm text-stone-500">Aucune donnée.</p>
            @endif
        </x-card>

        <x-card>
            <p class="eyebrow mb-4 text-olive-600">Répartition par catégorie</p>
            @if(!empty($metrics['by_category']))
                <dl class="grid gap-4 sm:grid-cols-2">
                    @foreach($metrics['by_category'] as $category => $count)
                        <div class="flex items-center justify-between p-3 rounded-lg bg-stone-50">
                            <span class="font-medium">{{ ucfirst($category) }}</span>
                            <span class="font-display text-2xl">{{ $count }}</span>
                        </div>
                    @endforeach
                </dl>
            @else
                <p class="text-sm text-stone-500">Aucune donnée.</p>
            @endif
        </x-card>
    </div>

    <div class="grid gap-8 lg:grid-cols-2 mb-10">
        <x-card>
            <p class="eyebrow mb-4 text-olive-600">Performance temporelle</p>
            <div class="space-y-3">
                <div class="flex justify-between"><span>Temps médian de résolution</span><span class="font-display text-xl">{{ $metrics['median_resolution_time_hours'] ?? '—' }} h</span></div>
                <div class="flex justify-between"><span>Temps moyen de résolution</span><span class="font-display text-xl">{{ $metrics['avg_resolution_time_hours'] ?? '—' }} h</span></div>
                <div class="flex justify-between"><span>Résolues sous 48h</span><span class="font-display text-xl text-olive-600">{{ $metrics['resolved_under_48h_pct'] ?? '—' }}%</span></div>
                <div class="flex justify-between"><span>Réouvertures</span><span class="font-display text-xl">{{ $metrics['reopened_complaints'] }}</span></div>
                <div class="flex justify-between"><span>Réclamations en retard</span><span class="font-display text-xl text-red-600">{{ $metrics['overdue_complaints'] }}</span></div>
            </div>
        </x-card>

        <x-card>
            <p class="eyebrow mb-4 text-olive-600">Charge de travail par admin</p>
            @if($adminWorkload->isNotEmpty())
                <div class="space-y-3">
                    @foreach($adminWorkload as $admin)
                        <div class="flex flex-wrap items-center justify-between gap-3 p-3 rounded-lg bg-stone-50">
                            <div>
                                <p class="font-semibold">{{ $admin['name'] }}</p>
                                <p class="text-xs text-stone-500">{{ $admin['email'] }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="bg-gold text-gold-800 px-2 py-0.5 rounded">{{ $admin['open'] }} ouvertes</span>
                                <span class="bg-olive-100 text-olive-800 px-2 py-0.5 rounded">{{ $admin['in_review'] }} en cours</span>
                                @if($admin['overdue'] > 0)
                                    <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded">{{ $admin['overdue'] }} en retard</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-stone-500">Aucune réclamation assignée.</p>
            @endif
        </x-card>
    </div>

    <x-card>
        <p class="eyebrow mb-4 text-olive-600">Tendance sur 30 jours</p>
        @if(isset($metrics['trend']['labels']) && count($metrics['trend']['labels']))
            <canvas id="trendChart" height="100"></canvas>
            <script>
                new Chart(document.getElementById('trendChart'), {
                    type: 'line',
                    data: {
                        labels: @json($metrics['trend']['labels']),
                        datasets: [{
                            label: 'Réclamations créées',
                            data: @json($metrics['trend']['data']),
                            borderColor: '#586a3d',
                            backgroundColor: 'rgba(88, 106, 61, 0.1)',
                            tension: 0.3,
                            fill: true
                        }]
                    },
                    options: { responsive: true, plugins: { legend: { display: false } } }
                });
            </script>
        @else
            <p class="text-sm text-stone-500">Aucune donnée de tendance.</p>
        @endif
    </x-card>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endpush