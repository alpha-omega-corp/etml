@props([
    'variant' => 'default',  // default | accent | success | warning | danger | info
    'outline' => false,
    'dot' => false,          // show a leading status dot
    'icon' => null,
])

<span {{ $attributes->class([
    'badge',
    'badge--'.$variant => $variant !== 'default',
    'badge--outline' => $outline,
]) }}>
    @if ($dot)
        <span class="badge__dot" aria-hidden="true"></span>
    @elseif ($icon)
        <x-ui.icon :name="$icon" />
    @endif

    {{ $slot }}
</span>
