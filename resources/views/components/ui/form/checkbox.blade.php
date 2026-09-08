@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'value' => '1',
    'checked' => false,
    'id' => null,
])

@php
    $id ??= $name ? 'check-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name) : 'check-'.uniqid();
    $message = $error ?? ($name && $errors->has($name) ? $errors->first($name) : null);
    $isChecked = $name ? (bool) old($name, $checked) : $checked;
@endphp

<div class="field">
    <label class="choice" for="{{ $id }}">
        <input
            {{ $attributes->class('choice__control') }}
            type="checkbox"
            id="{{ $id }}"
            @if ($name) name="{{ $name }}" @endif
            value="{{ $value }}"
            @checked($isChecked)
            @if ($message) aria-invalid="true" @endif
            @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        >

        <span class="choice__text">
            <span class="choice__label">{{ $label ?? $slot }}</span>

            @if ($hint)
                <span class="choice__hint" id="{{ $id }}-hint">{{ $hint }}</span>
            @endif
        </span>
    </label>

    @if ($message)
        <p class="field__error">
            <x-ui.icon name="alert-circle" />
            {{ $message }}
        </p>
    @endif
</div>
