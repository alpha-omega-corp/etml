@props([
    'variant' => 'info',   // info | success | warning | danger
    'title' => null,
    'duration' => 5000,    // milliseconds; 0 keeps it on screen
])

@php
    $icons = [
        'info' => 'info',
        'success' => 'check-circle',
        'warning' => 'alert-triangle',
        'danger' => 'alert-circle',
    ];
@endphp

<div
    {{ $attributes->class(['toast', 'toast--'.$variant]) }}
    data-toast
    data-toast-duration="{{ $duration }}"
    role="status"
>
    <x-ui.icon :name="$icons[$variant] ?? 'info'" class="toast__icon" />

    <div class="toast__content">
        @if ($title)
            <p class="toast__title">{{ $title }}</p>
        @endif

        <p class="toast__message">{{ $slot }}</p>
    </div>

    <button type="button" class="toast__dismiss" data-toast-dismiss>
        <span class="visually-hidden">Dismiss</span>
        <x-ui.icon name="x" />
    </button>
</div>
