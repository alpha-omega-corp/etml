@props([
    'language',
    'unit',
    'cards',
    'states' => [],
    'selections' => [],   // the user's own lists these words can go into
])

@php
    // A selection is cut out of a list somebody else wrote; it is not itself
    // a list to cut from, so it carries no ticks.
    $canPick = ! $unit->kind->isPersonal() && count($cards) > 0;
@endphp

{{--
    The card game. It is the same module for every language and both kinds of
    unit: the only things that change are the words it is handed and the two
    labels on the faces. `components/deck.js` reads the deck out of the
    `#deck-data` script at the bottom and drives everything from there.
--}}
<div class="deck @if ($unit->kind === \App\Support\UnitKind::Verbs) deck--verbs @endif">

    {{-- The tools and the filters sit ABOVE the card, and the readout below
         it. The reading-direction pill is the one control that is not here:
         it lives in `.deck-head`, opposite the title. `deck.js` binds it by
         `[data-direction]` anywhere on the page. --}}
    <div class="deck__tools">
        <button type="button" class="vocab-open" data-modal-open="vocab">
            <x-ui.icon name="book" size="16" />
            <span class="vocab-open__label">Tout le {{ $unit->kind === \App\Support\UnitKind::Verbs ? 'tableau' : 'vocabulaire' }}</span>
            <span class="vocab-open__count">{{ count($cards) }}</span>
        </button>

        <x-ui.button type="button" size="sm" icon="shuffle" id="btnShuffle" class="deck__shuffle">Mélanger</x-ui.button>
    </div>

    <div class="deck__filters btn-group" role="group" aria-label="Filtrer les cartes">
        <button type="button" class="btn btn--sm" data-filter="all" aria-pressed="true">Tous</button>
        <button type="button" class="btn btn--sm" data-filter="known" aria-pressed="false">
            Connus <span class="deck__filter-count" id="knownCount">0</span>
        </button>
        <button type="button" class="btn btn--sm" data-filter="review" aria-pressed="false">
            À revoir <span class="deck__filter-count" id="reviewCount">0</span>
        </button>
    </div>

    <div class="deck__stage">
        <button type="button" class="flashcard" id="card" aria-label="Retourner la carte">
            <span class="flashcard__face">
                <span class="flashcard__lang" id="frontLang">Français</span>
                <span class="flashcard__mark" id="frontMark"></span>
                <span class="flashcard__word" id="frontWord"></span>
                <x-ui.icon name="inbox" id="frontEmpty" class="flashcard__empty" size="44" label="Liste vide" hidden />
                <x-ui.icon name="refresh" class="flashcard__flip" size="18" />
            </span>
            <span class="flashcard__face flashcard__face--back">
                <span class="flashcard__lang" id="backLang">{{ $language->faceLabel() }}</span>
                <span class="flashcard__word" id="backWord"></span>
                <span class="flashcard__example" id="example"></span>
            </span>
        </button>

        <span class="shuffle-fx" id="shuffleFx" aria-hidden="true">
            <x-ui.icon name="sparkles" class="shuffle-fx__icon" />
        </span>
    </div>

    <p class="visually-hidden" role="status" id="shuffleSay"></p>

    <div class="deck__meta">
        <span class="deck__position" id="posLabel">1 / {{ count($cards) }}</span>
        <div class="deck__progress"><div class="deck__progress-fill" id="progressFill"></div></div>
    </div>

    {{-- L'ordre des enfants de cette barre PORTE la mise en page et ne change
         pas : #btnPrev en premier, #btnNext en dernier. Ne les réordonnez pas —
         l'explosion WAAPI prend `button.querySelector('svg')` comme glyphe,
         donc aucun svg décoratif ne doit précéder l'icône dans l'une ou
         l'autre décision. --}}
    <div class="deck__actions">
        <button type="button" class="btn btn--icon" id="btnPrev" aria-label="Carte précédente">
            <x-ui.icon name="chevron-left" />
        </button>

        <div class="deck__decide">
            <button type="button" class="btn btn--decision btn--review" id="btnReview">
                <x-ui.icon name="clock" />
                <span class="btn__label">À revoir</span>
                <span class="btn__flash" aria-hidden="true"></span>
                <span class="burst" aria-hidden="true"></span>
            </button>

            <button type="button" class="btn btn--decision btn--known" id="btnKnown">
                <x-ui.icon name="check" />
                <span class="btn__label">Je sais</span>
                <span class="btn__flash" aria-hidden="true"></span>
                <span class="burst" aria-hidden="true"></span>
            </button>
        </div>

        <button type="button" class="btn btn--icon" id="btnNext" aria-label="Carte suivante">
            <x-ui.icon name="chevron-right" />
        </button>
    </div>

    <p class="deck__sync" id="syncNote" role="status"></p>

</div>

<x-ui.modal id="vocab" title="Tout le vocabulaire" class="modal--full">
    <div class="vocab__search">
        <input
            type="search"
            id="vocabSearch"
            class="input"
            placeholder="Rechercher en français ou en {{ mb_strtolower($language->name) }}…"
            autocomplete="off"
            aria-label="Rechercher un mot"
        >
        <p class="vocab__count" id="vocabCount"></p>
    </div>

    <x-ui.table compact>
        <x-slot:head>
            <tr>
                @if ($canPick)
                    <th scope="col" class="vocab__pick"><span class="visually-hidden">Choisir</span></th>
                @endif
                <th scope="col">Français</th>
                <th scope="col">{{ $language->faceLabel() }}</th>
            </tr>
        </x-slot:head>

        <template id="vocabRows"></template>
    </x-ui.table>

    <p class="vocab__empty" id="vocabEmpty" hidden>Aucun mot ne correspond.</p>

    {{-- Ticking words here is how a user makes a deck of their own: the ticks
         are checkboxes bound to the form in the footer by `form=`, so the list
         can scroll under a bar that never moves. `deck.js` builds them with
         the rows. --}}
    @if ($canPick)
        <x-slot:footer>
            <form
                id="selection-form"
                method="POST"
                action="{{ route('selections.store') }}"
                class="pick"
                data-pick
            >
                @csrf
                <input type="hidden" name="unit" value="{{ $unit->id }}">

                <p class="pick__count" id="pickCount" role="status">Aucun mot choisi</p>

                <button type="button" class="btn btn--sm pick__clear" data-pick-clear hidden>
                    Tout décocher
                </button>

                @if (count($selections) > 0)
                    <select class="select pick__target" name="selection" data-pick-target aria-label="Ajouter à">
                        <option value="">Nouvelle sélection</option>
                        @foreach ($selections as $selection)
                            <option value="{{ $selection->id }}">{{ $selection->name }}</option>
                        @endforeach
                    </select>
                @endif

                <input
                    type="text"
                    class="input pick__name"
                    name="name"
                    maxlength="120"
                    placeholder="Nom de la sélection"
                    aria-label="Nom de la sélection"
                    data-pick-name
                >

                <x-ui.button type="submit" variant="primary" size="sm" icon="check" data-pick-submit disabled>
                    Créer la sélection
                </x-ui.button>
            </form>
        </x-slot:footer>
    @endif
</x-ui.modal>

{{-- The deck itself, handed to the module. JSON in a `type="application/json"`
     block is inert: nothing here is executed, and `<` and `&` are escaped so
     no string in a word list can close the tag early. --}}
@php
    $deckData = json_encode([
        'cards' => $cards,
        'states' => (object) $states,
        'labels' => [
            'native' => 'Français',
            'target' => $language->faceLabel(),
        ],
        'statusUrl' => route('cards.status', ['card' => '__ID__']),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<script type="application/json" id="deck-data">{!! $deckData !!}</script>
