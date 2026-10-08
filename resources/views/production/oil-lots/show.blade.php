@extends('layouts.admin')
@section('title', 'Oil lot '.$oilLot->lot_number)
@section('content')
    @php($request = $oilLot->millRequest)
    @php($harvest = $request?->harvest)
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Lot {{ $oilLot->lot_number }}</p>
            <h1 class="display-title text-4xl">{{ $oilLot->quality_grade?->label() ?? 'Ungraded oil' }}{{ $oilLot->liters !== null ? ' · '.$oilLot->liters.' L' : '' }}</h1>
            <p class="mt-4 text-sm text-stone-500">
                @if($harvest)
                    Harvest #{{ $harvest->id }} · {{ $harvest->farm->name }}
                @else
                    Declared directly on your account
                @endif
            </p>
        </div>
        <a class="btn-secondary" href="{{ route('producer.oil-lots.index') }}">Back to oil lots</a>
    </div>
    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <x-card>
            <h2 class="font-display text-2xl">Oil details</h2>
            <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2">
                <div><dt class="text-stone-500">Lot number</dt><dd class="mt-2 font-semibold">{{ $oilLot->lot_number }}</dd></div>
                <div><dt class="text-stone-500">Quality grade</dt><dd class="mt-2 font-semibold">@if($oilLot->quality_grade)<span class="role-badge">{{ $oilLot->quality_grade->label() }}</span>@else<span class="text-stone-500">Not graded</span>@endif</dd></div>
                <div><dt class="text-stone-500">Volume</dt><dd class="mt-2 font-semibold">{{ $oilLot->liters !== null ? $oilLot->liters.' L' : 'Not recorded' }}</dd></div>
                <div><dt class="text-stone-500">Production date</dt><dd class="mt-2 font-semibold">{{ $oilLot->production_date?->isoFormat('ll') ?? 'Not recorded' }}</dd></div>
                <div><dt class="text-stone-500">Mill</dt><dd class="mt-2 font-semibold">{{ $request?->targetLabel() ?? 'Not recorded' }}</dd></div>
                <div><dt class="text-stone-500">Appointment</dt><dd class="mt-2 font-semibold">{{ $request?->appointment_date?->isoFormat('ll') ?? '—' }}</dd></div>
                <div><dt class="text-stone-500">Harvest</dt><dd class="mt-2 font-semibold">@if($harvest)<a class="text-link" href="{{ route('producer.harvests.show', $harvest->id) }}">#{{ $harvest->id }} ({{ $harvest->quantity_kg !== null ? $harvest->quantity_kg.' kg' : 'quantity unknown' }})</a>@else—@endif</dd></div>
                <div><dt class="text-stone-500">Farm</dt><dd class="mt-2 font-semibold">{{ $harvest ? $harvest->farm->name.' ('.$harvest->farm->governorate.')' : '—' }}</dd></div>
                @if($oilLot->notes)<div><dt class="text-stone-500">Notes</dt><dd class="mt-2 whitespace-pre-line">{{ $oilLot->notes }}</dd></div>@endif
            </dl>
        </x-card>
        <x-card>
            <h2 class="font-display text-2xl">Traceability</h2>
            @if($harvest)
                <p class="mt-3 text-sm leading-6 text-stone-500">This oil was produced from olives harvested on {{ $harvest->harvest_date->isoFormat('ll') }} at {{ $harvest->farm->name }} using the <strong>{{ $harvest->method->label() }}</strong> method.</p>
                @if($harvest->notes)<p class="mt-4 whitespace-pre-line border-t border-stone-100 pt-5 text-sm leading-6 text-stone-600">Harvest notes: {{ $harvest->notes }}</p>@endif
                @if($request->message || $request->response_message)
                    <p class="mt-4 whitespace-pre-line border-t border-stone-100 pt-5 text-sm leading-6 text-stone-600">Mill communication:
                        @if($request->message)<br>Producer: {{ $request->message }}@endif
                        @if($request->response_message)<br>Mill: {{ $request->response_message }}@endif
                    </p>
                @endif
            @else
                <p class="mt-3 text-sm leading-6 text-stone-500">This lot was registered on your account without a milling record, so there is no harvest or mill to trace back to yet.</p>
            @endif
            @if($oilLot->certificateRequests->isNotEmpty())
                <p class="mt-4 whitespace-pre-line border-t border-stone-100 pt-5 text-sm leading-6 text-stone-600">Certification request{{ $oilLot->certificateRequests->count() > 1 ? 's' : '' }}: {{ $oilLot->certificateRequests->pluck('status')->join(', ') }}</p>
            @endif
        </x-card>
    </div>
@endsection
