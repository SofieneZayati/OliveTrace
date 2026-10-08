@extends('layouts.admin')
@section('title', 'Farm details')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-4 text-olive-600">{{ $farm->governorate }} / Farm #{{ $farm->id }}</p><h1 class="display-title text-4xl sm:text-5xl">{{ $farm->name }}</h1><p class="mt-4 text-sm text-stone-500">Managed by {{ $farm->producerProfile->display_name }}</p></div><div class="flex flex-wrap gap-3">@can('create', [\App\Models\Production\Harvest::class, $farm])<a class="btn-primary" href="{{ route('producer.harvests.create', ['farm' => $farm->id]) }}">Add a harvest <x-icon name="arrow" /></a>@endcan<a class="btn-secondary" href="{{ route($admin ? 'admin.farms.edit' : 'producer.farms.edit', $farm->id) }}">Edit farm <x-icon name="arrow" /></a></div></div>
    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <x-card>
            <span class="role-badge">{{ $farm->status->label() }}</span>
            <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2">
                @foreach(['Governorate' => $farm->governorate, 'Delegation' => $farm->delegation, 'Area' => $farm->area_ha.' ha', 'Olive variety' => $farm->olive_variety, 'Farming type' => $farm->farming_type->label(), 'Irrigation' => $farm->irrigation_type->label(), 'Private latitude' => $farm->gps_lat, 'Private longitude' => $farm->gps_lng] as $label => $value)
                    <div><dt class="text-stone-500">{{ $label }}</dt><dd class="mt-2 font-semibold">{{ $value ?? 'Not provided' }}</dd></div>
                @endforeach
            </dl>
            <p class="mt-7 whitespace-pre-line border-t border-stone-100 pt-6 text-sm leading-7 text-stone-600">{{ $farm->description ?? 'No farm notes yet.' }}</p>
            <p class="mt-4 text-xs leading-5 text-stone-500">Farming practices are producer declarations. They do not establish an organic certificate.</p>
        </x-card>
        <div class="space-y-6">
            <x-card><h2 class="font-display text-2xl">Public origin</h2><p class="mt-3 text-sm leading-6 text-stone-500">Selected fields can introduce this origin to consumers. The farm and producer profile must both allow publication and remain enabled.</p>
                @if($publicOrigin !== null)
                    <a class="btn-secondary mt-5" href="{{ route('origin.farms.show', $farm->id) }}">View public origin card <x-icon name="arrow" /></a>
                @else<p class="mt-5 text-sm font-medium">This origin is currently private or unavailable.</p>@endif
            </x-card>
            @include('production.farms.assistant')
        </div>
    </div>
    <x-card class="mt-8">
        @if($admin)
            <h2 class="font-display text-2xl">Farm moderation</h2><p class="my-4 text-sm text-stone-500">Disabling a farm removes it from public display and future harvest selection. Existing history stays intact.</p>
            <form method="POST" action="{{ route('admin.farms.status', $farm->id) }}" class="flex flex-wrap items-end gap-4">@csrf @method('PATCH')<x-production-field name="status" label="Farm status" :value="$farm->status->value" :options="collect(\App\Enums\FarmStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])->all()" :required="true" /><button class="btn-primary" type="submit">Update status</button></form>
        @else
            <h2 class="font-display text-2xl">Preserve the origin history</h2><p class="my-4 max-w-3xl text-sm leading-6 text-stone-500">Archive a parcel when it is no longer used. Deletion removes an unlinked farm; if harvest history exists, the farm is archived instead.</p>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('producer.farms.archive', $farm->id) }}">@csrf @method('PATCH')<button type="submit" class="btn-secondary">Archive farm</button></form>
                <form method="POST" action="{{ route('producer.farms.destroy', $farm->id) }}" x-data @submit="if (!confirm('Delete this farm if unlinked, or archive it if harvest history exists?')) $event.preventDefault()">@csrf @method('DELETE')<button class="btn-danger" type="submit">Delete farm</button></form>
            </div>
        @endif
    </x-card>
    <x-card class="mt-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><h2 class="font-display text-2xl">Harvests on this farm</h2><p class="mt-3 text-sm text-stone-500">{{ count($harvests) }} recorded on this parcel.</p></div>
            @can('create', [\App\Models\Production\Harvest::class, $farm])<a class="btn-primary" href="{{ route('producer.harvests.create', ['farm' => $farm->id]) }}">Add a harvest <x-icon name="arrow" /></a>@endcan
        </div>
        @if(count($harvests))
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach($harvests as $harvest)
                    <div class="rounded-xl border border-stone-100 p-5">
                        <div class="flex items-center justify-between gap-3"><span class="text-sm font-semibold">{{ $harvest->quantity_kg }} kg</span><span class="role-badge">{{ $harvest->status->label() }}</span></div>
                        <p class="mt-3 text-sm leading-6 text-stone-500">{{ $harvest->harvest_date->isoFormat('ll') }} · {{ $harvest->method->label() }}<br />Milling: {{ $harvest->millingStatus()?->label() ?? 'Not requested' }}</p>
                        <a class="text-link mt-4 flex items-center justify-between" href="{{ route($admin ? 'admin.harvests.show' : 'producer.harvests.show', $harvest->id) }}">View harvest <x-icon name="arrow" /></a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="mt-4 text-sm leading-6 text-stone-500">No harvest declared yet{{ $admin ? '.' : ' — add your first one to start the traceability story.' }}</p>
        @endif
    </x-card>
@endsection
