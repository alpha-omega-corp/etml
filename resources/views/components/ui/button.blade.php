@props([
    'variant' => 'default',   // default | primary | danger | ghost | link
    'size' => null,           // sm | lg
    'href' => null,           // renders an <a> instead of a <button>
    'type' => 'submit',
    'icon' => null,           // leading icon name
    'trailingIcon' => null,
    'iconOnly' => false,      // square button; put the accessible name in `label`
    'label' => null,          // accessible name when there is no visible text
    'block' => false,
    'pill' => false,
    'loading' => false,
    'disabled' => false,
])

@php
    $classes = [
        'btn',
        'btn--'.$variant => $variant !== 'default',
        'btn--'.$size => $size,
        'btn--block' => $block,
        'btn--pill' => $pill,
        'btn--icon' => $iconOnly,
        'btn--loading' => $loading,
    ];

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->class($classes) }}
    @if ($href)
        href="{{ $disabled || $loading ? '#' : $href }}"
        @if ($disabled || $loading) aria-disabled="true" tabindex="-1" @endif
    @else
        type="{{ $type }}"
        @disabled($disabled || $loading)
    @endif
    @if ($loading) aria-busy="true" @endif
    @if ($label) aria-label="{{ $label }}" @endif
>
    @if ($loading)
        <span class="spinner spinner--sm" aria-hidden="true"></span>
    @elseif ($icon)
        <x-ui.icon :name="$icon" />
    @endif

    @if (! $iconOnly && trim($slot) !== '')
        <span class="btn__label">{{ $slot }}</span>
    @endif

    @if ($trailingIcon && ! $loading)
        <x-ui.icon :name="$trailingIcon" />
    @endif
</{{ $tag }}>
