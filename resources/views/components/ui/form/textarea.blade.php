@props([
    'name' => null,
    'label' => null,
    'hint' => null,
    'error' => null,
    'value' => null,
    'rows' => 4,
    'required' => false,
    'id' => null,
    'bare' => false,
])

@php
    $id ??= $name ? 'input-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name) : 'input-'.uniqid();
    $message = $error ?? ($name && $errors->has($name) ? $errors->first($name) : null);
    $resolved = $name ? old($name, $value) : $value;
    $describedBy = $message ? $id.'-error' : ($hint ? $id.'-hint' : null);

    $control = $attributes->class('textarea')->merge(array_filter([
        'id' => $id,
        'name' => $name,
        'rows' => $rows,
        'required' => $required ? 'required' : null,
        'aria-invalid' => $message ? 'true' : null,
        'aria-describedby' => $describedBy,
    ], fn ($value) => ! is_null($value)));
@endphp

@if ($bare)
    <textarea {{ $control }}>{{ $resolved }}</textarea>
@else
    <x-ui.form.field
        :name="$name"
        :label="$label"
        :hint="$hint"
        :error="$error"
        :required="$required"
        :for="$id"
    >
        <textarea {{ $control }}>{{ $resolved }}</textarea>
    </x-ui.form.field>
@endif
