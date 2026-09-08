@props([
    'icon' => 'inbox',
    'title',
    'message' => null,
])

<div {{ $attributes->class('empty-state') }}>
    <div class="empty-state__icon">
        <x-ui.icon :name="$icon" />
    </div>

    <p class="empty-state__title">{{ $title }}</p>

    @if ($message)
        <p class="empty-state__message">{{ $message }}</p>
    @endif

    @isset($actions)
        <div class="empty-state__actions cluster">{{ $actions }}</div>
    @endisset
</div>
