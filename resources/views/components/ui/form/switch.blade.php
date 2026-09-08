@props([
    'name' => null,
    'label' => null,
    'value' => '1',
    'checked' => false,
    'id' => null,
])

@php
    $id ??= $name ? 'switch-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name) : 'switch-'.uniqid();
    $isChecked = $name ? (bool) old($name, $checked) : $checked;
@endphp

<label class="switch" for="{{ $id }}">
    <input
        {{ $attributes->class('switch__control') }}
        type="checkbox"
        role="switch"
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        value="{{ $value }}"
        @checked($isChecked)
    >

    <span class="choice__text">{{ $label ?? $slot }}</span>
</label>
