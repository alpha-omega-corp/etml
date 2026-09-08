@props([
    'name' => null,     // used for the initials fallback and the alt text
    'src' => null,
    'size' => null,     // xs | sm | lg | xl
    'square' => false,
])

@php
    // First letter of the first two words: "Ada Lovelace" -> "AL".
    $initials = collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp

<span {{ $attributes->class([
    'avatar',
    'avatar--'.$size => $size,
    'avatar--square' => $square,
]) }} @if ($name && ! $src) title="{{ $name }}" @endif>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name ?? '' }}" loading="lazy" decoding="async">
    @elseif ($initials !== '')
        <span aria-hidden="true">{{ $initials }}</span>
        <span class="visually-hidden">{{ $name }}</span>
    @else
        <x-ui.icon name="user" class="icon--muted" />
    @endif
</span>
