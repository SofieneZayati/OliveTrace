@props(['light' => false])
<span {{ $attributes->class(['brand', 'brand-light' => $light]) }}>
    <span class="brand-mark"><x-icon name="leaf" /></span>
    <span>Olive<span class="brand-trace">Trace</span><span class="brand-dot">.</span></span>
</span>
