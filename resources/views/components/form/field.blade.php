@props(['name', 'label', 'type' => 'text', 'hint' => null, 'required' => false, 'value' => null, 'autocomplete' => null, 'inputmode' => null, 'maxlength' => null])
@php($id = 'f-'.str_replace(['[', ']', '.'], '-', $name))
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="block text-sm font-semibold">
        {{ $label }} @unless($required)<span class="font-normal text-slate">(προαιρετικό)</span>@endunless
    </label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}"
           @if($required) required @endif
           @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
           @if($inputmode) inputmode="{{ $inputmode }}" @endif
           @if($maxlength) maxlength="{{ $maxlength }}" @endif
           @error($name) aria-invalid="true" aria-describedby="{{ $id }}-error" @elseif($hint) aria-describedby="{{ $id }}-hint" @enderror
           {{ $attributes->except('class') }}
           class="mt-1.5 block w-full rounded-xl border bg-surface px-4 py-3 text-[16px] outline-none transition-colors focus:border-onair focus:ring-2 focus:ring-onair/25 @error($name) border-red-500 @else border-line @enderror">
    @error($name)
        <p id="{{ $id }}-error" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @else
        @if($hint)<p id="{{ $id }}-hint" class="mt-1.5 text-sm text-slate">{{ $hint }}</p>@endif
    @enderror
</div>
