@props(['name', 'label', 'value' => '', 'type' => 'text', 'options' => null, 'required' => false, 'help' => null])
<div>
    <label for="{{ $name }}" class="form-label">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label>
    @if($options !== null)
        <select id="{{ $name }}" name="{{ $name }}" @required($required) aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" aria-describedby="{{ $name }}-feedback" {{ $attributes->class(['mt-2 w-full']) }}>
            <option value="">Select {{ strtolower($label) }}</option>
            @foreach($options as $key => $text)<option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $text }}</option>@endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="4" @required($required) aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" aria-describedby="{{ $name }}-feedback" {{ $attributes->class(['mt-2 w-full rounded-xl border-stone-300 text-sm focus:border-olive-500 focus:ring-olive-500']) }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required) aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" aria-describedby="{{ $name }}-feedback" {{ $attributes->class(['form-input mt-2 w-full']) }}>
    @endif
    <div id="{{ $name }}-feedback">
        @if($help)<p class="mt-2 text-xs leading-5 text-stone-500">{{ $help }}</p>@endif
        <x-input-error :messages="$errors->get($name)" class="mt-2" />
    </div>
</div>
