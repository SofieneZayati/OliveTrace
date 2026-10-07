@props(['origin'])
<article {{ $attributes->class(['surface-card']) }} aria-label="Farm origin">
    <div class="mb-6 flex items-center gap-3"><span class="grid h-12 w-12 place-items-center rounded-full bg-olive-50"><x-icon name="leaf" class="h-6 w-6 text-olive-600" /></span><p class="eyebrow text-olive-600">Rooted in {{ $origin['governorate'] }}</p></div>
    <h2 class="font-display text-3xl sm:text-4xl">{{ $origin['name'] }}</h2>
    <p class="mt-3 text-stone-500">Grown by {{ $origin['producer'] }}</p>
    <dl class="mt-7 grid gap-6 text-sm sm:grid-cols-2">
        @foreach(['Region' => $origin['governorate'].($origin['delegation'] ? ' · '.$origin['delegation'] : ''), 'Olive variety' => $origin['olive_variety'], 'Parcel area' => $origin['area_ha'].' ha', 'Irrigation' => $origin['irrigation_type'], 'Declared farming practice' => $origin['farming_type']] as $label => $value)
            <div><dt class="text-stone-500">{{ $label }}</dt><dd class="mt-2 font-semibold">{{ $value }}</dd></div>
        @endforeach
    </dl>
    <p class="mt-7 border-t border-stone-100 pt-5 text-xs leading-6 text-stone-500">Origin information is declared by the producer. It is not a certification or proof of environmental performance.</p>
</article>
