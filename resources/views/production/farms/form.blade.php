@extends('layouts.admin')
@section('title', $farm ? 'Edit farm' : 'Add farm')
@section('content')
    <div class="mb-8 max-w-2xl"><p class="eyebrow mb-4 text-olive-600">Record the origin</p><h1 class="display-title text-4xl">{{ $farm ? 'Keep your farm story accurate.' : 'A new parcel. A new beginning.' }}</h1><p class="mt-4 text-sm leading-6 text-stone-500">Describe the land and cultivation practices. Fields marked * are required.</p></div>
    <x-card class="max-w-4xl">
        <form method="POST" action="{{ $farm ? route($admin ? 'admin.farms.update' : 'producer.farms.update', $farm->id) : route('producer.farms.store') }}" class="space-y-7">
            @csrf @if($farm) @method('PATCH') @endif
            <div class="grid gap-6 sm:grid-cols-2">
                <x-production-field name="name" label="Farm or parcel name" :value="$farm?->name" :required="true" maxlength="150" />
                <x-production-field name="governorate" label="Governorate" :value="$farm?->governorate" :required="true" :options="array_combine(\App\Support\ProductionOptions::GOVERNORATES, \App\Support\ProductionOptions::GOVERNORATES)" />
                <x-production-field name="delegation" label="Delegation" :value="$farm?->delegation" maxlength="100" />
                <x-production-field name="area_ha" label="Area (hectares)" type="number" :value="$farm?->areaHa" :required="true" min="0.01" max="99999999.99" step="0.01" />
                <x-production-field name="olive_variety" label="Olive variety" :value="$farm?->oliveVariety" :required="true" maxlength="100" placeholder="e.g. Chemlali" />
                <x-production-field name="farming_type" label="Farming type" :value="$farm?->farmingType->value" :required="true" :options="collect(\App\Enums\FarmingType::cases())->mapWithKeys(fn($type) => [$type->value => $type->label()])->all()" help="A producer declaration; organic farming is not proof of certification." />
                <x-production-field name="irrigation_type" label="Irrigation type" :value="$farm?->irrigationType->value" :required="true" :options="collect(\App\Enums\IrrigationType::cases())->mapWithKeys(fn($type) => [$type->value => $type->label()])->all()" />
            </div>
            <fieldset class="rounded-xl border border-stone-200 p-5"><legend class="px-2 text-sm font-semibold">Optional private coordinates</legend><p class="mb-5 text-xs leading-5 text-stone-500">Provide both coordinates or leave both empty. These are not shown on the public origin card.</p><div class="grid gap-6 sm:grid-cols-2">
                <x-production-field name="gps_lat" label="Latitude" type="number" :value="$farm?->gpsLat" min="-90" max="90" step="0.0000001" />
                <x-production-field name="gps_lng" label="Longitude" type="number" :value="$farm?->gpsLng" min="-180" max="180" step="0.0000001" />
            </div></fieldset>
            <x-production-field name="description" label="Farm notes" type="textarea" :value="$farm?->description" maxlength="3000" />
            <div class="rounded-xl bg-olive-50 p-5"><x-production-toggle name="is_public" :checked="$farm?->isPublic ?? false" label="Publish selected origin fields. My producer profile must also allow public display. Contact details, notes and coordinates stay private." /></div>
            <div class="flex flex-wrap gap-3 border-t border-stone-100 pt-6"><x-primary-button>{{ $farm ? 'Save farm' : 'Create farm' }}</x-primary-button><a class="btn-secondary" href="{{ route($admin ? 'admin.farms.index' : 'producer.farms.index') }}">Cancel</a></div>
        </form>
    </x-card>
@endsection
