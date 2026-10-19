@props(['picker' => false, 'points' => []])
<div x-data="farmMap({ picker: @js($picker), points: @js($points), tiles: @js(config('farm-map.tile_url')), attribution: @js(config('farm-map.attribution')), center: @js(config('farm-map.center')), zoom: @js(config('farm-map.zoom')), maxZoom: @js(config('farm-map.max_zoom')) })" {{ $attributes->class(['farm-map-widget']) }}>
    {{ $slot }}
    <div class="mt-5 flex flex-wrap items-center gap-3">
        <button type="button" class="btn-secondary" @click="load()" x-show="!loaded" :disabled="loading"><span x-text="loading ? 'Loading map…' : 'Load private map'">Load private map</span></button>
        <button type="button" class="btn-secondary" @click="fit()" x-show="loaded" x-cloak>{{ $picker ? 'Center on coordinates' : 'Fit farm locations' }}</button>
        <button type="button" class="text-link" @click="showTunisia()" x-show="loaded" x-cloak>Show Tunisia</button>
        @if($picker)<button type="button" class="text-link" @click="clear()">Clear coordinates</button>@endif
    </div>
    <p class="mt-3 text-xs leading-6 text-stone-500">Map tiles by OpenStreetMap. Loading the map shares your IP and the viewed map area with the tile provider. Exact farm coordinates remain restricted to the owner and administrators in OliveTrace.</p>
    @if($picker)<p class="mt-2 text-xs leading-6 text-stone-500">Click the map or drag the marker to select a point. You can also type both coordinates above. Changes are saved only when you save the farm.</p>@endif
    <p class="mt-3 text-sm text-olive-700" role="status" aria-live="polite" x-text="status"></p>
    <p class="mt-3 rounded-xl bg-amber-50 p-4 text-sm leading-6 text-amber-900" role="status" x-show="error" x-text="error" x-cloak></p>
    <div x-ref="canvas" class="farm-map-canvas mt-4" x-show="loaded" x-cloak role="region" aria-label="{{ $picker ? 'Private farm location picker' : 'Private farm locations map' }}"></div>
    <noscript><p class="mt-3 text-sm text-stone-500">JavaScript is needed for the interactive map. @if($picker)You can still enter and save the coordinate fields above.@else Farm links and saved coordinate details remain available.@endif</p></noscript>
</div>
