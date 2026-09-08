@props([
    'size' => null,          // sm | lg
    'label' => 'Loading',
])

<span {{ $attributes->class(['spinner', 'spinner--'.$size => $size]) }} role="status">
    <span class="visually-hidden">{{ $label }}</span>
</span>
