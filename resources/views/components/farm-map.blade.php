@props(['picker' => false, 'points' => [], 'publicMap' => false])
<div x-data="farmMap({ picker: @js($picker), publicMap: @js($publicMap), points: @js($points), tiles: @js(config('farm-map.tile_url')), attribution: @js(config('farm-map.attribution')), center: @js(config('farm-map.center')), zoom: @js(config('farm-map.zoom')), maxZoom: @js($publicMap ? 8 : config('farm-map.max_zoom')) })" {{ $attributes->class(['farm-map-widget']) }}>
    {{ $slot }}
    <div class="mt-5 flex flex-wrap items-center gap-3">
        <button type="button" class="btn-secondary" @click="load()" x-show="!loaded" :disabled="loading"><span x-text="loading ? 'Loading map…' : @js($publicMap ? 'Load origin map' : 'Load private map')">{{ $publicMap ? 'Load origin map' : 'Load private map' }}</span></button>
        <button type="button" class="btn-secondary" @click="fit()" x-show="loaded" x-cloak>{{ $publicMap ? 'Fit origin regions' : ($picker ? 'Center on coordinates' : 'Fit farm locations') }}</button>
        <button type="button" class="text-link" @click="showTunisia()" x-show="loaded" x-cloak>Show Tunisia</button>
        @if($picker)<button type="button" class="text-link" @click="clear()">Clear coordinates</button>@endif
    </div>
    <p class="mt-3 text-xs leading-6 text-stone-500">Map tiles by OpenStreetMap. Loading the map shares your IP and the viewed map area with the tile provider. {{ $publicMap ? 'Pins show approximate governorate reference points, not farm locations.' : 'Exact farm coordinates remain restricted to the owner and administrators in OliveTrace.' }}</p>
    @if($publicMap)<p class="mt-1 text-xs text-stone-500">Region reference data: <a class="underline" href="https://www.geoboundaries.org/" target="_blank" rel="noopener">geoBoundaries</a> / © OpenStreetMap contributors, <a class="underline" href="https://opendatacommons.org/licenses/odbl/1-0/" target="_blank" rel="noopener">ODbL 1.0</a>.</p>@endif
    @if($picker)<p class="mt-2 text-xs leading-6 text-stone-500">Click the map or drag the marker to select a point. You can also type both coordinates above. Changes are saved only when you save the farm.</p>@endif
    <p class="mt-3 text-sm text-olive-700" role="status" aria-live="polite" x-text="status"></p>
    <p class="mt-3 rounded-xl bg-amber-50 p-4 text-sm leading-6 text-amber-900" role="status" x-show="error" x-text="error" x-cloak></p>
    <div x-ref="canvas" class="farm-map-canvas mt-4" x-show="loaded" x-cloak role="region" aria-label="{{ $publicMap ? 'Approximate product origin regions' : ($picker ? 'Private farm location picker' : 'Private farm locations map') }}"></div>
    <noscript><p class="mt-3 text-sm text-stone-500">JavaScript is needed for the interactive map. @if($publicMap)The published origin region remains available in the product details.@elseif($picker)You can still enter and save the coordinate fields above.@else Farm links and saved coordinate details remain available.@endif</p></noscript>
</div>
