@props([
    'name' => null,
    'type' => 'text',
    'label' => null,
    'hint' => null,
    'error' => null,
    'value' => null,        // falls back to old($name) first
    'required' => false,
    'id' => null,
    'bare' => false,        // render the control alone, with no field wrapper
    'prefix' => null,       // leading addon text
    'suffix' => null,       // trailing addon text
])

@php
    $id ??= $name ? 'input-'.str_replace(['[', ']', '.'], ['-', '', '-'], $name) : 'input-'.uniqid();
    $message = $error ?? ($name && $errors->has($name) ? $errors->first($name) : null);
    $resolved = $name ? old($name, $value) : $value;

    // Point the control at whichever description actually gets rendered.
    $describedBy = $message ? $id.'-error' : ($hint ? $id.'-hint' : null);

    // Built once so the markup below can stay readable. Attributes passed at the
    // call site win over these defaults, and `class` is merged rather than replaced.
    $control = $attributes->class('input')->merge(array_filter([
        'type' => $type,
        'id' => $id,
        'name' => $name,
        'value' => $resolved,
        'required' => $required ? 'required' : null,
        'aria-invalid' => $message ? 'true' : null,
        'aria-describedby' => $describedBy,
    ], fn ($value) => ! is_null($value)));
@endphp

@if ($bare)
    <input {{ $control }}>
@else
    <x-ui.form.field
        :name="$name"
        :label="$label"
        :hint="$hint"
        :error="$error"
        :required="$required"
        :for="$id"
    >
        @if ($prefix || $suffix)
            <div class="input-group">
                @if ($prefix)
                    <span class="input-group__addon">{{ $prefix }}</span>
                @endif

                <input {{ $control }}>

                @if ($suffix)
                    <span class="input-group__addon">{{ $suffix }}</span>
                @endif
            </div>
        @else
            <input {{ $control }}>
        @endif
    </x-ui.form.field>
@endif
