@props([
    'tabs',            // ['key' => 'Label', ...]
    'active' => null,  // defaults to the first key
    'label' => 'Sections',
])

@php
    $tabs = collect($tabs);
    $active ??= $tabs->keys()->first();
    $id = $attributes->get('id', 'tabs-'.uniqid());
@endphp

{{--
    <x-ui.tabs :tabs="['overview' => 'Overview', 'activity' => 'Activity']">
        <x-slot:overview>...</x-slot:overview>
        <x-slot:activity>...</x-slot:activity>
    </x-ui.tabs>

    Each key needs a matching slot. Panels are rendered in the order of the
    array, and the first one is selected unless `active` says otherwise.
--}}

<div {{ $attributes->except('id')->class('tabs') }} data-tabs>
    <div class="tabs__list" role="tablist" aria-label="{{ $label }}">
        @foreach ($tabs as $key => $tabLabel)
            <button
                type="button"
                id="{{ $id }}-tab-{{ $key }}"
                class="tabs__tab"
                role="tab"
                aria-selected="{{ $key === $active ? 'true' : 'false' }}"
                aria-controls="{{ $id }}-panel-{{ $key }}"
                tabindex="{{ $key === $active ? '0' : '-1' }}"
            >{{ $tabLabel }}</button>
        @endforeach
    </div>

    @foreach ($tabs as $key => $tabLabel)
        <div
            id="{{ $id }}-panel-{{ $key }}"
            class="tabs__panel"
            role="tabpanel"
            aria-labelledby="{{ $id }}-tab-{{ $key }}"
            tabindex="0"
            @if ($key !== $active) hidden @endif
        >{{ ${$key} ?? '' }}</div>
    @endforeach
</div>
