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
                <option value="7d" @selected($period === '7d')>7 jours</option>
                <option value="30d" @selected($period === '30d')>30 jours</option>
                <option value="90d" @selected($period === '90d')>90 jours</option>
            </select>
        </form>
    </div>

    @if($urgentNotifications->isNotEmpty())
        <x-card class="mb-8">
            <h2 class="font-display mb-4 text-2xl">Alertes prioritaires</h2>
            @foreach($urgentNotifications as $alert)
                <p class="mt-3 text-sm"><a class="text-link" href="{{ route('admin.consumer.complaints.show', $alert->related_complaint_id) }}">{{ $alert->title }}</a></p>
            @endforeach
        </x-card>
    @endif

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
        <p class="eyebrow mb-4 text-olive-600">Tendance · {{ count($metrics['trend']['labels']) }} jours</p>
        @if(isset($metrics['trend']['labels']) && count($metrics['trend']['labels']))
            <div class="flex h-32 items-end gap-1" role="img" aria-label="Nombre de réclamations créées par jour">
                @foreach($metrics['trend']['data'] as $index => $count)
                    <div class="min-w-0 flex-1 rounded-t bg-olive-600" style="height: {{ max(2, $count / max(1, max($metrics['trend']['data'])) * 100) }}%" title="{{ $metrics['trend']['labels'][$index] }} : {{ $count }}"></div>
                @endforeach
            </div>
            <details class="mt-5 text-sm"><summary>Voir les valeurs quotidiennes</summary>
                <dl class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach($metrics['trend']['labels'] as $index => $label)
                        <div><dt>{{ $label }}</dt><dd>{{ $metrics['trend']['data'][$index] }}</dd></div>
                    @endforeach
                </dl>
            </details>
        @else
            <p class="text-sm text-stone-500">Aucune donnée de tendance.</p>
        @endif
    </x-card>
@endsection
