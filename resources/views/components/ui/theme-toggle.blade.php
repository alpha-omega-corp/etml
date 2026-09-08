@props([
    'cycle' => false,   // step light -> dark -> system instead of just flipping
])

{{--
    The icons are all rendered and toggled with `hidden` by
    resources/js/components/theme.js, so the correct one shows without a
    second server round trip.
--}}
<button
    type="button"
    {{ $attributes->class('btn btn--ghost btn--icon') }}
    data-theme-toggle
    @if ($cycle) data-theme-cycle @endif
    aria-pressed="false"
>
    <span class="visually-hidden">Toggle dark mode</span>
    <x-ui.icon name="sun" data-theme-icon="light" />
    <x-ui.icon name="moon" data-theme-icon="dark" hidden />
</button>
