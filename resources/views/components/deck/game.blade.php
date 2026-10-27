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

    $listTitle = $unit->kind === \App\Support\UnitKind::Verbs ? 'Tout le tableau' : 'Tout le vocabulaire';
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
            <span class="vocab-open__label">{{ $listTitle }}</span>
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

<x-ui.modal
    id="vocab"
    :title="$listTitle"
    :description="$unit->name.' · '.count($cards).' '.(count($cards) === 1 ? 'mot' : 'mots')"
    class="modal--full"
>
    <div @class(['vocab', 'vocab--pick' => $canPick])>
        <div class="vocab__bar">
            <div class="vocab__search">
                <label class="vocab__field">
                    <x-ui.icon name="search" size="18" class="vocab__field-icon" />
                    <input
                        type="search"
                        id="vocabSearch"
                        class="input vocab__input"
                        placeholder="Rechercher un mot…"
                        autocomplete="off"
                        autocapitalize="off"
                        spellcheck="false"
                        enterkeyhint="search"
                        aria-label="Rechercher en français ou en {{ mb_strtolower($language->name) }}"
                    >
                </label>
                <p class="vocab__count" id="vocabCount" aria-live="polite"></p>
            </div>

            {{-- The same marks as the deck's filters, so the words still to
                 learn can be read — and, with the picker, ticked — at once. --}}
            <div class="vocab__filters">
                <div class="vocab__chips" role="group" aria-label="Filtrer la liste">
                    <button type="button" class="vocab__chip" data-vocab-filter="all" aria-pressed="true">
                        Tous <span class="vocab__chip-count">{{ count($cards) }}</span>
                    </button>
                    <button type="button" class="vocab__chip" data-vocab-filter="review" aria-pressed="false">
                        <span class="vocab__dot vocab__dot--review" aria-hidden="true"></span>
                        À revoir <span class="vocab__chip-count" data-vocab-tally="review">0</span>
                    </button>
                    <button type="button" class="vocab__chip" data-vocab-filter="known" aria-pressed="false">
                        <span class="vocab__dot vocab__dot--known" aria-hidden="true"></span>
                        Connus <span class="vocab__chip-count" data-vocab-tally="known">0</span>
                    </button>
                    <button type="button" class="vocab__chip" data-vocab-filter="new" aria-pressed="false">
                        <span class="vocab__dot" aria-hidden="true"></span>
                        Nouveaux <span class="vocab__chip-count" data-vocab-tally="new">0</span>
                    </button>
                </div>

                @if ($canPick)
                    <button type="button" class="vocab__all" data-pick-all>
                        Tout cocher
                    </button>
                @endif
            </div>
        </div>

        {{-- The column heads only exist where the two words sit side by side.
             On a phone they stack, French over the other language, and the
             heads would label nothing. --}}
        <div class="vocab__head" aria-hidden="true">
            @if ($canPick)
                <span></span>
            @endif
            <span>Français</span>
            <span>{{ $language->faceLabel() }}</span>
        </div>

        {{-- One line per word, built by `deck.js`. With the picker, a line is
             a `<label>` around its tick, so the whole line toggles it without
             a script having to aim for the box. --}}
        <ul class="vocab__list" id="vocabList" role="list"></ul>

        <div class="vocab__empty" id="vocabEmpty" hidden>
            <x-ui.icon name="search" size="28" />
            <p>Aucun mot ne correspond<span id="vocabQuery"></span>.</p>
            <button type="button" class="btn btn--sm" id="vocabReset">Tout afficher</button>
        </div>
    </div>

    {{-- Ticking words here is how a user makes a deck of their own: the ticks
         are checkboxes bound to the form in the footer by `form=`, so the list
         can scroll under a bar that never moves. Until a word is ticked the bar
         is one line of help; the controls only take room once they can act. --}}
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

                <div class="pick__summary">
                    <p class="pick__count" id="pickCount" role="status">Cochez des mots pour en faire une sélection.</p>

                    <button type="button" class="btn btn--ghost btn--sm pick__clear" data-pick-clear hidden>
                        Tout décocher
                    </button>
                </div>

                <div class="pick__controls" data-pick-controls hidden>
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
                        placeholder="Ex. Mots difficiles"
                        aria-label="Nom de la sélection"
                        enterkeyhint="done"
                        data-pick-name
                    >

                    <x-ui.button type="submit" variant="primary" icon="check" class="pick__submit" data-pick-submit disabled>
                        Créer la sélection
                    </x-ui.button>
                </div>
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
