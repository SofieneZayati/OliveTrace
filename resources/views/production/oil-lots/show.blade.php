@extends('layouts.admin')
@section('title', 'Oil lot {{ $oilLot->lot_number }}')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Lot {{ $oilLot->lot_number }}</p>
            <h1 class="display-title text-4xl">{{ $oilLot->quality_grade->label() }} · {{ $oilLot->liters }} L</h1>
            <p class="mt-4 text-sm text-stone-500">Harvest #{{ $oilLot->millRequest->harvest->id }} · {{ $oilLot->millRequest->harvest->farm->name }}</p>
        </div>
        <a class="btn-secondary" href="{{ route('producer.oil-lots.index') }}">Back to oil lots</a>
    </div>
    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <x-card>
            <h2 class="font-display text-2xl">Oil details</h2>
            <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2">
                <div><dt class="text-stone-500">Lot number</dt><dd class="mt-2 font-semibold">{{ $oilLot->lot_number }}</dd></div>
                <div><dt class="text-stone-500">Quality grade</dt><dd class="mt-2 font-semibold"><span class="role-badge">{{ $oilLot->quality_grade->label() }}</span></dd></div>
                <div><dt class="text-stone-500">Volume</dt><dd class="mt-2 font-semibold">{{ $oilLot->liters }} L</dd></div>
                <div><dt class="text-stone-500">Production date</dt><dd class="mt-2 font-semibold">{{ $oilLot->production_date->isoFormat('ll') }}</dd></div>
                <div><dt class="text-stone-500">Mill</dt><dd class="mt-2 font-semibold">{{ $oilLot->millRequest->targetLabel() }}</dd></div>
                <div><dt class="text-stone-500">Appointment</dt><dd class="mt-2 font-semibold">{{ $oilLot->millRequest->appointment_date?->isoFormat('ll') ?? '—' }}</dd></div>
                <div><dt class="text-stone-500">Harvest</dt><dd class="mt-2 font-semibold"><a class="text-link" href="{{ route('producer.harvests.show', $oilLot->millRequest->harvest->id) }}">#{{ $oilLot->millRequest->harvest->id }} ({{ $oilLot->millRequest->harvest->quantity_kg }} kg)</a></dd></div>
                <div><dt class="text-stone-500">Farm</dt><dd class="mt-2 font-semibold">{{ $oilLot->millRequest->harvest->farm->name }} ({{ $oilLot->millRequest->harvest->farm->governorate }})</dd></div>
                @if($oilLot->notes)<div><dt class="text-stone-500">Notes</dt><dd class="mt-2 whitespace-pre-line">{{ $oilLot->notes }}</dd></div>@endif
            </dl>
        </x-card>
        <x-card>
            <h2 class="font-display text-2xl">Traceability</h2>
            <p class="mt-3 text-sm leading-6 text-stone-500">This oil was produced from olives harvested on {{ $oilLot->millRequest->harvest->harvest_date->isoFormat('ll') }} at {{ $oilLot->millRequest->harvest->farm->name }} using the <strong>{{ $oilLot->millRequest->harvest->method->label() }}</strong> method.</p>
            @if($oilLot->millRequest->harvest->notes)<p class="mt-4 whitespace-pre-line border-t border-stone-100 pt-5 text-sm leading-6 text-stone-600">Harvest notes: {{ $oilLot->millRequest->harvest->notes }}</p>@endif
            @if($oilLot->millRequest->message || $oilLot->millRequest->response_message)
                <p class="mt-4 whitespace-pre-line border-t border-stone-100 pt-5 text-sm leading-6 text-stone-600">Mill communication:
                    @if($oilLot->millRequest->message)<br>Producer: {{ $oilLot->millRequest->message }}@endif
                    @if($oilLot->millRequest->response_message)<br>Mill: {{ $oilLot->millRequest->response_message }}@endif
                </p>
            @endif
        </x-card>
    </div>
@endsection