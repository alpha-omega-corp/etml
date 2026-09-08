@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,       // pass a string to override; otherwise read from $errors
    'required' => false,
    'for' => null,         // id of the control this label points at
    'inputId' => null,     // alias of `for`, for readability at call sites
])

@php
    $controlId = $for ?? $inputId;
    $message = $error ?? ($name && $errors->has($name) ? $errors->first($name) : null);
@endphp

{{--
    The label + control + hint/error unit.

    The `input`, `textarea`, `select` and `checkbox` components wrap themselves
    in one of these. Use it directly when you need a control this kit does not
    cover — a date picker, a rich text editor — and keep the same ids:

        <x-ui.form.field name="starts_at" label="Starts at" for="starts_at" hint="UTC">
            <input id="starts_at" name="starts_at" class="input" type="datetime-local">
        </x-ui.form.field>
--}}

<div {{ $attributes->class('field') }}>
    @if ($label)
        <label class="field__label" @if ($controlId) for="{{ $controlId }}" @endif>
            {{ $label }}

            @if ($required)
                <span class="field__required" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint && ! $message)
        <p class="field__hint" @if ($controlId) id="{{ $controlId }}-hint" @endif>{{ $hint }}</p>
    @endif

    @if ($message)
        <p class="field__error" @if ($controlId) id="{{ $controlId }}-error" @endif>
            <x-ui.icon name="alert-circle" />
            {{ $message }}
        </p>
    @endif
</div>
