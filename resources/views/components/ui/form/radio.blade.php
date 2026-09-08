@props([
    'name',
    'options' => [],   // ['value' => 'Label'] or ['value' => ['label' => '…', 'hint' => '…']]
    'label' => null,   // the group's legend
    'hint' => null,
    'error' => null,
    'selected' => null,
])

@php
    $message = $error ?? ($errors->has($name) ? $errors->first($name) : null);
    $resolved = old($name, $selected);
@endphp

<fieldset {{ $attributes->class('field') }}>
    @if ($label)
        <legend class="field__label">{{ $label }}</legend>
    @endif

    @if ($hint)
        <p class="field__hint">{{ $hint }}</p>
    @endif

    <div class="choice-group">
        @foreach ($options as $optionValue => $option)
            @php
                $option = is_array($option) ? $option : ['label' => $option];
                $id = 'radio-'.$name.'-'.$optionValue;
            @endphp

            <label class="choice" for="{{ $id }}">
                <input
                    class="choice__control"
                    type="radio"
                    id="{{ $id }}"
                    name="{{ $name }}"
                    value="{{ $optionValue }}"
                    @checked((string) $optionValue === (string) $resolved)
                >

                <span class="choice__text">
                    <span class="choice__label">{{ $option['label'] }}</span>

                    @if ($option['hint'] ?? null)
                        <span class="choice__hint">{{ $option['hint'] }}</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>

    @if ($message)
        <p class="field__error">
            <x-ui.icon name="alert-circle" />
            {{ $message }}
        </p>
    @endif
</fieldset>
