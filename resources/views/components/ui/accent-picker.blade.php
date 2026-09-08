@props([
    'label' => 'Accent colour',
])

@php
    /**
     * The accent axis of the theme. Independent of light/dark, so a choice here
     * survives a mode toggle. The swatch colours are the *light* base of each
     * palette, hardcoded here because a CSS custom property cannot be read for
     * an accent that is not currently applied — this is the one place in the
     * app where an accent literal is correct.
     *
     * Palettes live in resources/scss/base/_root.scss; behaviour in
     * resources/js/components/accent.js. Add one in all three places.
     */
    $accents = [
        'green' => ['label' => 'Green', 'swatch' => '#0f5f52'],
        'orange' => ['label' => 'Orange', 'swatch' => '#a14310'],
        'blue' => ['label' => 'Blue', 'swatch' => '#1a4d9c'],
        'purple' => ['label' => 'Purple', 'swatch' => '#5b3a9e'],
    ];
@endphp

<x-ui.dropdown :label="$label" {{ $attributes }}>
    <x-slot:trigger>
        <button
            type="button"
            class="btn btn--ghost btn--icon"
            data-dropdown-trigger
            aria-expanded="false"
            aria-haspopup="true"
        >
            <span class="visually-hidden">{{ $label }}</span>
            <x-ui.icon name="palette" />
        </button>
    </x-slot:trigger>

    {{-- A radiogroup rather than a menu: exactly one accent is active, and the
         arrow keys should move between them the way native radios do. --}}
    <div class="accent-picker" role="radiogroup" aria-label="{{ $label }}" data-accent-picker>
        @foreach ($accents as $name => $accent)
            <button
                type="button"
                class="accent-picker__option"
                role="radio"
                aria-checked="false"
                tabindex="-1"
                data-accent-option="{{ $name }}"
                style="--swatch: {{ $accent['swatch'] }}"
            >
                <span class="accent-picker__swatch" aria-hidden="true">
                    <x-ui.icon name="check" size="14" />
                </span>
                {{ $accent['label'] }}
            </button>
        @endforeach
    </div>
</x-ui.dropdown>
