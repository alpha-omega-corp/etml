@props([
    'cycle' => false,   // step light -> dark -> system instead of just flipping
])

{{--
    The icons are all rendered and toggled with `hidden` by
    resources/js/components/theme.js, so the correct one shows without a
    second server round trip. The shipped defaults below are the pre-JS state
    and must match the CSS floor — dark — or the sun shows and `aria-pressed`
    reads false for one frame on every load.
--}}
<button
    type="button"
    {{ $attributes->class('btn btn--ghost btn--icon') }}
    data-theme-toggle
    @if ($cycle) data-theme-cycle @endif
    aria-pressed="true"
>
    <span class="visually-hidden">Basculer le thème</span>
    <x-ui.icon name="sun" data-theme-icon="light" hidden />
    <x-ui.icon name="moon" data-theme-icon="dark" />
</button>
