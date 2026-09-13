@props([
    'title' => null,
    'bodyClass' => '',
    'drawer' => false,
    'drawerLabel' => 'Chapitres',
])

{{--
    The application shell: the bar that is on every page, and whatever the
    page puts under it. `drawer` adds the hamburger that opens the unit rail,
    which only the deck pages have.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="app-body {{ $bodyClass }}">

<header class="deck-bar">
    @if ($drawer)
        <button
            type="button"
            class="deck-bar__menu"
            data-drawer-toggle
            aria-controls="subnav"
            aria-expanded="false"
        >
            <span class="visually-hidden">{{ $drawerLabel }}</span>
            <x-ui.icon name="menu" size="20" />
        </button>
    @endif

    <a href="{{ route('languages.index') }}" class="deck-bar__title">{{ config('app.name') }}</a>

    <div class="cluster push-end" style="--cluster-gap: 0">
        <x-ui.theme-toggle />
        <x-ui.accent-picker />

        <x-ui.dropdown>
            <x-slot:trigger>
                <button type="button" class="btn btn--ghost deck-bar__user" data-dropdown-trigger aria-expanded="false" aria-haspopup="true">
                    <x-ui.icon name="user" />
                    <span class="deck-bar__name">{{ auth()->user()->username }}</span>
                </button>
            </x-slot:trigger>

            <x-ui.dropdown.item icon="refresh" variant="danger" :data-reset="route('cards.reset')">
                Effacer mes listes
            </x-ui.dropdown.item>
            <x-ui.dropdown.divider />
            <x-ui.dropdown.item type="submit" form="logout-form" icon="log-out">Changer de nom</x-ui.dropdown.item>
            <x-ui.dropdown.divider />
            @if ($isAdmin)
                <x-ui.dropdown.item type="submit" form="admin-logout-form" icon="shield">Quitter le mode admin</x-ui.dropdown.item>
            @else
                <x-ui.dropdown.item href="{{ route('admin.login') }}" icon="lock">Mode administrateur</x-ui.dropdown.item>
            @endif
        </x-ui.dropdown>
    </div>
</header>

<form id="logout-form" method="POST" action="{{ route('logout') }}" hidden>@csrf</form>
@if ($isAdmin)
    <form id="admin-logout-form" method="POST" action="{{ route('admin.logout') }}" hidden>@csrf</form>
@endif

{{ $slot }}

@include('partials.flash')

</body>
</html>
