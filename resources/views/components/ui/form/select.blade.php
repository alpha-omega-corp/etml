@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'options' => [],        // ['value' => 'Label', ...]
    'selected' => null,
    'placeholder' => null,  // adds a disabled first option
    'required' => false,
    'id' => null,
])

@php
    $id ??= $name ? 'input-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name) : 'input-'.uniqid();
    $message = $error ?? ($name && $errors->has($name) ? $errors->first($name) : null);
    $resolved = $name ? old($name, $selected) : $selected;
    $describedBy = $message ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp

<x-ui.form.field
    :name="$name"
    :label="$label"
    :hint="$hint"
    :error="$error"
    :required="$required"
    :for="$id"
>
    <select
        {{ $attributes->class('select') }}
        id="{{ $id }}"
        @if ($name) name="{{ $name }}" @endif
        @required($required)
        @if ($message) aria-invalid="true" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
    >
        @if ($placeholder)
            <option value="" disabled @selected(blank($resolved))>{{ $placeholder }}</option>
        @endif

        @if ($slot->isNotEmpty())
            {{ $slot }}
        @else
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $optionValue === (string) $resolved)>{{ $optionLabel }}</option>
            @endforeach
        @endif
    </select>
</x-ui.form.field>
