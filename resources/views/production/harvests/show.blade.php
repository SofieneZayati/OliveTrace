@extends('layouts.admin')
@section('title', 'Harvest details')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
        <div><p class="eyebrow mb-4 text-olive-600">{{ $harvest->farm->governorate }} / Harvest #{{ $harvest->id }}</p><h1 class="display-title text-4xl sm:text-5xl">{{ $harvest->quantity_kg !== null ? $harvest->quantity_kg.' kg of olives' : 'Quantity unknown' }}</h1><p class="mt-4 text-sm text-stone-500">{{ $harvest->farm->name }} · {{ $harvest->farm->producerProfile->display_name }}</p></div>
        <div class="flex flex-wrap gap-3">
            <a class="btn-secondary" href="{{ route($admin ? 'admin.harvests.index' : 'producer.harvests.index') }}">Back to harvests</a>
            @can('update', $harvest)<a class="btn-primary" href="{{ route('producer.harvests.edit', $harvest->id) }}">Edit harvest <x-icon name="arrow" /></a>@endcan
            @can('delete', $harvest)
                <form method="POST" action="{{ route($admin ? 'admin.harvests.destroy' : 'producer.harvests.destroy', $harvest->id) }}" x-data @submit="if (!confirm('Delete this harvest? This cannot be undone.')) $event.preventDefault()">@csrf @method('DELETE')<button class="btn-danger" type="submit">Delete harvest</button></form>
            @endcan
        </div>
    </div>
    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <x-card>
            <div class="flex flex-wrap items-center gap-3"><span class="role-badge">{{ $harvest->status->label() }}</span><span class="text-sm text-stone-500">Milling: {{ $harvest->millingStatus()?->label() ?? 'Not requested' }}</span></div>
            <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2">
                @foreach(['Farm' => $harvest->farm->name, 'Producer' => $harvest->farm->producerProfile->display_name, 'Governorate' => $harvest->farm->governorate, 'Harvest date' => $harvest->harvest_date->isoFormat('ll'), 'Expected end' => $harvest->expected_end_date?->isoFormat('ll') ?? 'Not set', 'Harvesting method' => $harvest->method->label(), 'Declared quantity' => $harvest->quantity_kg !== null ? $harvest->quantity_kg.' kg' : 'Unknown', 'Requested so far' => $harvest->requestedQuantity().' kg'] as $label => $value)
                    <div><dt class="text-stone-500">{{ $label }}</dt><dd class="mt-2 font-semibold">{{ $value }}</dd></div>
                @endforeach
            </dl>
            <p class="mt-7 whitespace-pre-line border-t border-stone-100 pt-6 text-sm leading-7 text-stone-600">{{ $harvest->notes ?? 'No harvest notes yet.' }}</p>
            @unless($harvest->millRequests->isEmpty())
                <p class="mt-4 text-xs leading-5 text-stone-500">This harvest has mill requests, so it is kept as traceability history and can no longer be deleted.</p>
            @endunless
        </x-card>
        <div class="space-y-6">
            <x-card>
                <h2 class="font-display text-2xl">Milling status</h2>
                @if($harvest->millingRequest)
                    <div class="mt-5 flex items-center justify-between gap-4"><span class="role-badge">{{ $harvest->millingRequest->status->label() }}</span><span class="text-sm font-semibold">{{ $harvest->millingRequest->targetLabel() }}</span></div>
                    <dl class="mt-6 grid gap-5 text-sm sm:grid-cols-2">
                        <div><dt class="text-stone-500">Quantity</dt><dd class="mt-2 font-semibold">{{ $harvest->millingRequest->quantity_kg }} kg</dd></div>
                        <div><dt class="text-stone-500">Requested for</dt><dd class="mt-2 font-semibold">{{ $harvest->millingRequest->requested_date->isoFormat('ll') }}</dd></div>
                        <div><dt class="text-stone-500">Appointment</dt><dd class="mt-2 font-semibold">{{ $harvest->millingRequest->appointment_date?->isoFormat('ll') ?? 'Waiting for the mill' }}</dd></div>
                        <div><dt class="text-stone-500">Milling operation</dt><dd class="mt-2 font-semibold">{{ $harvest->status->label() }}</dd></div>
                    </dl>
                    @if($harvest->millingRequest->response_message)<p class="mt-6 whitespace-pre-line border-t border-stone-100 pt-5 text-sm leading-6 text-stone-600">{{ $harvest->millingRequest->response_message }}</p>@endif
                @else
                    <p class="mt-3 text-sm leading-6 text-stone-500">No mill request yet. Send one while the harvest is running to schedule your slot at the mill.</p>
                @endif
            </x-card>
            @if($canSend)
                <x-card>
                    <h2 class="font-display text-2xl">Send to a mill</h2>
                    @if($harvest->remainingQuantity() !== null)
                        <p class="mt-3 text-sm leading-6 text-stone-500">{{ number_format($harvest->remainingQuantity(), 2) }} kg still available on this harvest.</p>
                    @else
                        <p class="mt-3 text-sm leading-6 text-stone-500">Total harvest quantity is unknown. Enter the amount you want to send to the mill.</p>
                    @endif
                    <form method="POST" action="{{ route('producer.mill-requests.store', $harvest->id) }}" class="mt-6 space-y-6">
                        @csrf
                        <x-production-field name="mill_id" label="Mill" :required="true" :options="$mills->mapWithKeys(fn($mill) => [$mill->id => $mill->name.' — '.$mill->region])->all() + ['external' => 'External mill (not registered)']" />
                        <x-production-field name="external_mill_name" label="External mill name" :value="old('external_mill_name')" maxlength="150" help="Only when you choose an external mill." />
                        <x-production-field name="requested_date" label="Requested date" type="date" :value="old('requested_date')" :required="true" min="today" />
                        <x-production-field name="quantity_kg" label="Quantity (kg)" type="number" :value="old('quantity_kg', $harvest->remainingQuantity())" :required="true" min="0.01" max="99999999.99" step="0.01" />
                        <x-production-field name="message" label="Message to the mill" type="textarea" :value="old('message')" maxlength="2000" />
                        <x-primary-button>Send request</x-primary-button>
                    </form>
                </x-card>
            @elseif(! $admin && $harvest->status !== \App\Enums\HarvestStatus::Milled && $activeRequest)
                <x-card>
                    <h2 class="font-display text-2xl">Waiting on the mill</h2>
                    <p class="mt-3 text-sm leading-6 text-stone-500">Your request to {{ $activeRequest->targetLabel() }} is {{ strtolower($activeRequest->status->label()) }}. You can cancel it while it is still pending.</p>
                    @can('cancel', $activeRequest)
                        <form method="POST" action="{{ route('producer.requests.cancel', $activeRequest->id) }}" class="mt-5" x-data @submit="if (!confirm('Cancel this mill request? The quantity becomes available again.')) $event.preventDefault()">@csrf @method('PATCH')<button class="btn-secondary" type="submit">Cancel request</button></form>
                    @endcan
                </x-card>
            @endif
        </div>
    </div>
    <x-card class="mt-8">
        <h2 class="font-display text-2xl">Mill requests</h2>
        @forelse($harvest->millRequests->sortByDesc('id') as $request)
            <div class="mt-6 flex flex-wrap items-start justify-between gap-4 border-t border-stone-100 pt-6">
                <div class="grid gap-4 sm:grid-cols-4">
                    <div><p class="text-xs text-stone-500">Mill</p><p class="mt-2 text-sm font-semibold">{{ $request->targetLabel() }}</p></div>
                    <div><p class="text-xs text-stone-500">Quantity</p><p class="mt-2 text-sm font-semibold">{{ $request->quantity_kg }} kg</p></div>
                    <div><p class="text-xs text-stone-500">Requested for</p><p class="mt-2 text-sm font-semibold">{{ $request->requested_date->isoFormat('ll') }}</p></div>
                    <div><p class="text-xs text-stone-500">Appointment</p><p class="mt-2 text-sm font-semibold">{{ $request->appointment_date?->isoFormat('ll') ?? '—' }}</p></div>
                </div>
                <div class="flex items-center gap-4"><span class="role-badge">{{ $request->status->label() }}</span>
                    @can('cancel', $request)
                        <form method="POST" action="{{ route('producer.requests.cancel', $request->id) }}" x-data @submit="if (!confirm('Cancel this mill request?')) $event.preventDefault()">@csrf @method('PATCH')<button class="btn-secondary" type="submit">Cancel</button></form>
                    @endcan
                </div>
            </div>
            @if($request->message || $request->response_message)
                <div class="mt-4 grid gap-4 border-t border-stone-100 pt-4 sm:grid-cols-2">
                    @if($request->message)<div><p class="text-xs text-stone-500">Message to the mill</p><p class="mt-2 text-sm leading-6 text-stone-600">{{ $request->message }}</p></div>@endif
                    @if($request->response_message)<div><p class="text-xs text-stone-500">Answer from the mill</p><p class="mt-2 text-sm leading-6 text-stone-600">{{ $request->response_message }}</p></div>@endif
                </div>
            @endif
        @empty
            <p class="mt-3 text-sm leading-6 text-stone-500">No requests yet. This harvest has not been offered to a mill.</p>
        @endforelse
    </x-card>
    @include('production.harvests.assistant')
@endsection
