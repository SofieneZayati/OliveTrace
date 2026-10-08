<x-card class="mt-8">
    <p class="eyebrow mb-3 text-olive-600">AI estimate</p>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-2xl">Expected oil output</h2>
            <p class="mt-2 text-sm text-stone-500">{{ $harvest->quantity_kg > 0 ? 'From '.number_format($harvest->quantity_kg, 0).' kg of olives.' : 'Estimate how much oil this harvest could produce.' }}</p>
        </div>
        <form method="POST" action="{{ route($admin ? 'admin.harvests.estimate' : 'producer.harvests.estimate', $harvest->id) }}" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <button class="btn-secondary" type="submit" :disabled="loading" @disabled(!$harvest->quantity_kg || $harvest->quantity_kg <= 0)><span x-show="!loading">Estimate oil quantity</span><span x-show="loading" x-cloak>Estimating…</span></button>
        </form>
    </div>
    @if(!$harvest->quantity_kg || $harvest->quantity_kg <= 0)
        <p class="mt-4 text-sm text-stone-500">Add the olive quantity to your harvest first.</p>
    @endif
    @isset($estimateResult)
        <div class="mt-6 border-t border-stone-100 pt-6" role="status">
            @if($estimateResult['available'])
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-olive-50 p-5"><p class="text-sm text-stone-500">Estimated oil quantity</p><p class="mt-2 text-3xl font-semibold text-olive-800">{{ number_format($estimateResult['estimate']['liters_min'], 1) }}–{{ number_format($estimateResult['estimate']['liters_max'], 1) }} <span class="text-lg">L</span></p></div>
                    <div class="rounded-xl border border-olive-100 p-5"><p class="text-sm text-stone-500">Oil yield</p><p class="mt-2 text-2xl font-semibold">{{ $estimateResult['estimate']['yield_min_percent'] }}–{{ $estimateResult['estimate']['yield_max_percent'] }}%</p><p class="mt-2 text-xs text-stone-500">Percentage of olive weight recovered as oil.</p></div>
                </div>
                <details class="mt-5 text-sm">
                    <summary class="cursor-pointer font-medium text-olive-700">What affects this estimate?</summary>
                    <p class="mt-3 leading-7 text-stone-600">{{ $estimateResult['estimate']['explanation'] }}</p>
                    <p class="mt-3 leading-7 text-stone-500">{{ $estimateResult['estimate']['limitations'] }}</p>
                </details>
            @else
                <p class="text-sm text-stone-500">{{ $estimateResult['message'] }}</p>
            @endif
        </div>
    @endisset
    <p class="mt-5 text-xs leading-6 text-stone-500">Estimated range. Actual quantity is confirmed after milling.</p>
</x-card>
