@props([
    'variant' => 'info',   // info | success | warning | danger
    'title' => null,
    'icon' => null,        // defaults to one matching the variant
    'dismissible' => false,
])

@php
    $icons = [
        'info' => 'info',
        'success' => 'check-circle',
        'warning' => 'alert-triangle',
        'danger' => 'alert-circle',
    ];

    $resolvedIcon = $icon ?? ($icons[$variant] ?? 'info');
@endphp

<div
    {{ $attributes->class(['alert', 'alert--'.$variant]) }}
    @if ($dismissible) data-dismissible @endif
    role="{{ in_array($variant, ['danger', 'warning'], true) ? 'alert' : 'status' }}"
>
    <x-ui.icon :name="$resolvedIcon" class="alert__icon" />

    <div class="alert__content">
        @if ($title)
            <p class="alert__title">{{ $title }}</p>
        @endif

        <div class="alert__message">{{ $slot }}</div>
    </div>

    @if ($dismissible)
        <button type="button" class="alert__dismiss" data-dismiss>
            <span class="visually-hidden">Dismiss</span>
            <x-ui.icon name="x" />
        </button>
    @endif
</div>
