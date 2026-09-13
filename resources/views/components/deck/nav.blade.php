@props([
    'language',
    'kind',
    'units',
    'known' => [],
    'current' => null,
])

{{--
    The rail listing the units of one kind — chapters or verb pages — with the
    open one marked. On phones it is a drawer; `components/drawer.js` opens it
    and `components/rename.js` handles the pencil.
--}}
<div class="nav-backdrop" data-drawer-backdrop aria-hidden="true"></div>

<nav class="subnav" id="subnav" data-drawer aria-label="{{ $kind->heading() }}">
    <div class="subnav__bar">
        <span class="nav-title">{{ $kind->heading() }}</span>
        <button type="button" class="subnav__close" data-drawer-close>
            <span class="visually-hidden">Fermer</span>
            <x-ui.icon name="x" size="18" />
        </button>
    </div>

    <a href="{{ route('program.show', $language) }}" class="subnav__back">
        <x-ui.icon name="arrow-left" size="14" />
        {{ $language->name }}
    </a>

    @if ($units->isEmpty())
        <p class="nav-empty">{{ $kind->none() }}</p>
    @else
        <ul class="nav-list">
            @foreach ($units as $item)
                <li
                    class="nav-item"
                    data-rename
                    data-name="{{ $item->name }}"
                    data-rename-url="{{ route('units.update', $item) }}"
                >
                    <a
                        href="{{ route('deck.show', [$language, $kind->slug(), $item]) }}"
                        class="nav-link @if ($current && $item->id === $current->id) is-current @endif"
                        @if ($current && $item->id === $current->id) aria-current="page" @endif
                    >
                        <span class="nav-link__label">{{ $item->name }}</span>
                        <span class="nav-link__count">{{ ($known[$item->id] ?? 0) }} / {{ $item->cards_count }}</span>
                    </a>

                    <input
                        type="text"
                        class="input nav-item__input"
                        value="{{ $item->name }}"
                        aria-label="Nom {{ $kind->ofThe() }}"
                        data-rename-input
                        hidden
                    >

                    <button
                        type="button"
                        class="nav-item__edit"
                        data-rename-edit
                        aria-label="Renommer « {{ $item->name }} »"
                    >
                        <x-ui.icon name="pencil" size="14" />
                    </button>

                    @if ($isAdmin)
                        <form
                            method="POST"
                            action="{{ route('units.destroy', $item) }}"
                            class="nav-item__delete"
                            onsubmit="return confirm('Supprimer « {{ $item->name }} » et ses {{ $item->cards_count }} mots ?')"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nav-item__edit nav-item__edit--danger" aria-label="Supprimer « {{ $item->name }} »">
                                <x-ui.icon name="trash" size="14" />
                            </button>
                        </form>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <a href="{{ route('units.create', ['language' => $language->slug, 'kind' => $kind->slug()]) }}" class="subnav__new">
        + {{ $kind->newLabel() }}
    </a>
</nav>
