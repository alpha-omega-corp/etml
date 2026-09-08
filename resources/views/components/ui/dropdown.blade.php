@props([
    'align' => 'end',        // start | end
    'label' => 'Open menu',  // accessible name for the default trigger
])

@php
    $id = 'dropdown-'.$attributes->get('id', uniqid());
@endphp

{{--
    <x-ui.dropdown>
        <x-slot:trigger>
            <button type="button" class="btn btn--sm" data-dropdown-trigger>Actions</button>
        </x-slot:trigger>

        <x-ui.dropdown.item href="#" icon="pencil">Edit</x-ui.dropdown.item>
        <x-ui.dropdown.divider />
        <x-ui.dropdown.item icon="trash" variant="danger">Delete</x-ui.dropdown.item>
    </x-ui.dropdown>

    Provide your own trigger via the `trigger` slot; it must carry
    `data-dropdown-trigger`. `aria-expanded` and `aria-controls` are wired for
    you at render time by the markup below when you use the default trigger.
--}}

<div {{ $attributes->except('id')->class('dropdown') }} data-dropdown>
    @isset($trigger)
        {{ $trigger }}
    @else
        <button
            type="button"
            class="btn btn--ghost btn--icon"
            data-dropdown-trigger
            aria-expanded="false"
            aria-controls="{{ $id }}"
            aria-haspopup="true"
        >
            <span class="visually-hidden">{{ $label }}</span>
            <x-ui.icon name="dots-vertical" />
        </button>
    @endisset

    <div id="{{ $id }}" class="dropdown__menu dropdown__menu--{{ $align }}" data-dropdown-menu role="menu" hidden>
        {{ $slot }}
    </div>
</div>
