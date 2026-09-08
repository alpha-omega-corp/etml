@props([
    'href' => null,
    'icon' => null,
    'variant' => null,   // danger
    'type' => 'button',
])

@php
    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    {{ $attributes->class(['dropdown__item', 'dropdown__item--'.$variant => $variant]) }}
    @if ($href) href="{{ $href }}" @else type="{{ $type }}" @endif
    role="menuitem"
>
    @if ($icon)
        <x-ui.icon :name="$icon" />
    @endif

    {{ $slot }}
</{{ $tag }}>
