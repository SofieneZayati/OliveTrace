@props(['value' => 0, 'count' => null])
@php $filled = max(0, min(5, (int) round((float) $value))); @endphp
<p {{ $attributes->merge(['class' => 'flex items-center gap-1 text-sm']) }}>
    <span class="text-gold" aria-hidden="true">{{ str_repeat('★', $filled) }}</span><span class="text-stone-300" aria-hidden="true">{{ str_repeat('★', 5 - $filled) }}</span>
    <span class="ml-1 font-semibold">{{ number_format((float) $value, 1) }} / 5</span>
    @if($count !== null)<span class="text-stone-500">({{ $count }})</span>@endif
    <span class="sr-only">{{ number_format((float) $value, 1) }} out of 5@if($count !== null) from {{ $count }} reviews@endif</span>
</p>
