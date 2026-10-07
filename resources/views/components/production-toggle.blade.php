@props(['name', 'label', 'checked' => false])
<div>
    <input type="hidden" name="{{ $name }}" value="0">
    <label class="flex items-start gap-3 text-sm" for="{{ $name }}">
        <input id="{{ $name }}" class="mt-1" type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))>
        <span>{{ $label }}</span>
    </label>
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
