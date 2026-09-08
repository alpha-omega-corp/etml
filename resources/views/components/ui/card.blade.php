@props([
    'title' => null,
    'subtitle' => null,
    'variant' => null,   // muted
    'flush' => false,    // remove body padding, e.g. when the body is a table
    'interactive' => false,
])

{{--
    <x-ui.card title="Revenue" subtitle="Last 30 days">
        <x-slot:actions><x-ui.button size="sm">Export</x-ui.button></x-slot:actions>
        ...body...
        <x-slot:footer>...</x-slot:footer>
    </x-ui.card>

    Passing `title` renders the header for you; use the `header` slot instead
    when you need full control of it.
--}}

<div {{ $attributes->class([
    'card',
    'card--'.$variant => $variant,
    'card--flush' => $flush,
    'card--interactive' => $interactive,
]) }}>
    @isset($header)
        <div class="card__header">{{ $header }}</div>
    @elseif ($title || isset($actions))
        <div class="card__header">
            <div>
                @if ($title)
                    <h3 class="card__title">{{ $title }}</h3>
                @endif

                @if ($subtitle)
                    <p class="card__subtitle">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                <div class="cluster push-end">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="card__body">{{ $slot }}</div>

    @isset($footer)
        <div class="card__footer">{{ $footer }}</div>
    @endisset
</div>
