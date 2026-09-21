<x-layouts.app :title="$unit->name" body-class="deck-body" drawer :drawer-label="$kind->heading()">

<div class="deck-shell">

    <x-deck.nav
        :language="$language"
        :kind="$kind"
        :units="$units"
        :known="$known"
        :current="$unit"
    />

    <main id="main" class="content">

        <div class="deck-head">
            <h1 class="deck-head__title">{{ $unit->name }}</h1>

            @if (count($deck) > 0)
                <div class="dir-pill" role="group" aria-label="Sens de traduction">
                    <button type="button" class="dir-pill__option" data-direction="native" aria-pressed="true">FR → {{ $language->shortLabel() }}</button>
                    <button type="button" class="dir-pill__option" data-direction="target" aria-pressed="false">{{ $language->shortLabel() }} → FR</button>
                </div>
            @endif
        </div>

        @if (count($deck) === 0)

            <div class="import">
                <p class="import__lead">
                    {{ ucfirst($kind->thisOne()) }} est vide. Collez la liste de mots au format
                    JSON pour créer le jeu de cartes.
                </p>

                <form method="POST" action="{{ route('units.import', $unit) }}" class="stack">
                    @csrf

                    <x-ui.form.textarea
                        name="import"
                        label="Mots (JSON)"
                        rows="12"
                        required
                        :placeholder="$wordsPlaceholder"
                    />

                    @include('partials.json-template')
                    @include('partials.word-formats')

                    <x-ui.button type="submit" variant="primary" size="lg" block icon="plus">
                        Créer les cartes
                    </x-ui.button>
                </form>
            </div>

        @else

            <x-deck.game
                :language="$language"
                :unit="$unit"
                :cards="$deck"
                :states="$states"
                :selections="$selections"
            />

        @endif

    </main>

</div>

</x-layouts.app>
