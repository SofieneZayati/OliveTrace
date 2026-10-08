@extends('layouts.admin')
@section('title', 'Mill request #{{ $request->id }}')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow mb-4 text-olive-600">Request #{{ $request->id }}</p>
            <h1 class="display-title text-4xl">Harvest #{{ $request->harvest->id }} · {{ $request->harvest->quantity_kg }} kg</h1>
            <p class="mt-4 text-sm text-stone-500">{{ $request->harvest->farm->name }} · {{ $request->harvest->farm->producerProfile->display_name }}</p>
        </div>
        <a class="btn-secondary" href="{{ route('mill.mill-requests.index') }}">Back to requests</a>
    </div>
    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <x-card>
            <div class="flex flex-wrap items-center gap-3"><span class="role-badge">{{ $request->status->label() }}</span><span class="text-sm text-stone-500">Quantity: {{ $request->quantity_kg }} kg</span></div>
            <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2">
                <div><dt class="text-stone-500">Farm</dt><dd class="mt-2 font-semibold">{{ $request->harvest->farm->name }}</dd></div>
                <div><dt class="text-stone-500">Producer</dt><dd class="mt-2 font-semibold">{{ $request->harvest->farm->producerProfile->display_name }}</dd></div>
                <div><dt class="text-stone-500">Governorate</dt><dd class="mt-2 font-semibold">{{ $request->harvest->farm->governorate }}</dd></div>
                <div><dt class="text-stone-500">Harvest date</dt><dd class="mt-2 font-semibold">{{ $request->harvest->harvest_date->isoFormat('ll') }}</dd></div>
                <div><dt class="text-stone-500">Harvest method</dt><dd class="mt-2 font-semibold">{{ $request->harvest->method->label() }}</dd></div>
                <div><dt class="text-stone-500">Requested for</dt><dd class="mt-2 font-semibold">{{ $request->requested_date->isoFormat('ll') }}</dd></div>
                <div><dt class="text-stone-500">Appointment</dt><dd class="mt-2 font-semibold">{{ $request->appointment_date?->isoFormat('ll') ?? 'Not scheduled' }}</dd></div>
                <div><dt class="text-stone-500">Message from producer</dt><dd class="mt-2 font-semibold whitespace-pre-line">{{ $request->message ?? '—' }}</dd></div>
            </dl>
            @if($request->response_message)<p class="mt-7 whitespace-pre-line border-t border-stone-100 pt-6 text-sm leading-6 text-stone-600">{{ $request->response_message }}</p>@endif
        </x-card>
        <div class="space-y-6">
            @if($request->oilLot)
                <x-card>
                    <h2 class="font-display text-2xl">Oil lot</h2>
                    <dl class="mt-5 grid gap-5 text-sm">
                        <div><dt class="text-stone-500">Lot number</dt><dd class="mt-2 font-semibold">{{ $request->oilLot->lot_number }}</dd></div>
                        <div><dt class="text-stone-500">Volume</dt><dd class="mt-2 font-semibold">{{ $request->oilLot->liters }} L</dd></div>
                        <div><dt class="text-stone-500">Quality</dt><dd class="mt-2 font-semibold"><span class="role-badge">{{ $request->oilLot->quality_grade->label() }}</span></dd></div>
                        <div><dt class="text-stone-500">Production date</dt><dd class="mt-2 font-semibold">{{ $request->oilLot->production_date->isoFormat('ll') }}</dd></div>
                        @if($request->oilLot->notes)<div><dt class="text-stone-500">Notes</dt><dd class="mt-2 whitespace-pre-line">{{ $request->oilLot->notes }}</dd></div>@endif
                    </dl>
                    <a class="mt-6 btn-secondary inline-flex" href="{{ route('mill.oil-lots.edit', $request->oilLot->id) }}">Edit oil lot <x-icon name="arrow" /></a>
                </x-card>
            @else
                @can('update', $request)
                    <x-card>
                        <h2 class="font-display text-2xl">Update status</h2>
                        <form method="POST" action="{{ route('mill.mill-requests.update', $request->id) }}" class="mt-5 space-y-6">
                            @csrf @method('PATCH')
                            <x-production-field name="status" label="Status" :value="$request->status->value" :required="true" :options="['pending' => 'Pending', 'accepted' => 'Accepted', 'refused' => 'Refused', 'completed' => 'Completed']" help="Set to Completed when milling is done — you will then create the oil lot." />
                            <x-production-field name="appointment_date" label="Appointment date" type="date" :value="$request->appointment_date?->toDateString()" min="today" help="When the producer should bring the olives." />
                            <x-production-field name="response_message" label="Message to producer" type="textarea" :value="$request->response_message" maxlength="3000" help="Optional note for the producer (e.g., refusal reason)." />
                            <x-primary-button>Save status</x-primary-button>
                        </form>
                    </x-card>
                @endif
            @endif
        </div>
    </div>
@endsection