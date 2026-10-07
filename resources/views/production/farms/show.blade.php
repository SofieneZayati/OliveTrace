@extends('layouts.admin')
@section('title', 'Farm details')
@section('content')
    <div class="mb-8 flex flex-wrap items-end justify-between gap-4"><div><p class="eyebrow mb-4 text-olive-600">{{ $farm->governorate }} / Farm #{{ $farm->id }}</p><h1 class="display-title text-4xl sm:text-5xl">{{ $farm->name }}</h1><p class="mt-4 text-sm text-stone-500">Managed by {{ $farm->producerProfile->displayName }}</p></div><a class="btn-primary" href="{{ route($admin ? 'admin.farms.edit' : 'producer.farms.edit', $farm->id) }}">Edit farm <x-icon name="arrow" /></a></div>
    <div class="grid gap-6 lg:grid-cols-[1.3fr_1fr]">
        <x-card>
            <span class="role-badge">{{ $farm->status->label() }}</span>
            <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2">
                @foreach(['Governorate' => $farm->governorate, 'Delegation' => $farm->delegation, 'Area' => $farm->areaHa.' ha', 'Olive variety' => $farm->oliveVariety, 'Farming type' => $farm->farmingType->label(), 'Irrigation' => $farm->irrigationType->label(), 'Private latitude' => $farm->gpsLat, 'Private longitude' => $farm->gpsLng] as $label => $value)
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
@endsection
