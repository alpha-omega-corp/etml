<x-layouts.app :title="$kind->newLabel()">

<main id="main" class="page-form">
    <a href="{{ route('program.show', $language) }}" class="page-form__back">
        <x-ui.icon name="arrow-left" size="16" />
        Retour à {{ $language->name }}
    </a>

    <h1 class="page-form__title">{{ $kind->newLabel() }} · {{ $language->name }}</h1>
    <p class="page-form__lead">
        {{ ucfirst($kind->aNew()) }} se crée à partir de sa liste de mots, en
        JSON. Collez-la ci-dessous : les cartes sont créées avec {{ $kind->thisOne() }}.
    </p>

    <form method="POST" action="{{ route('units.store') }}" class="stack">
        @csrf
        <input type="hidden" name="language" value="{{ $language->slug }}">
        <input type="hidden" name="kind" value="{{ $kind->slug() }}">

        <x-ui.form.input
            name="name"
            label="Nom {{ $kind->ofThe() }}"
            :placeholder="$kind === \App\Support\UnitKind::Verbs ? 'Verbes forts, p. 12' : 'II. Die Wohnung'"
            required
        />

        <x-ui.form.textarea
            name="words"
            label="Mots (JSON)"
            rows="14"
            required
            :placeholder="$wordsPlaceholder"
            :hint="'Un objet français vers '.mb_strtolower($language->name).', ou une liste d\'objets fr / '.$language->code.' / ex / sec.'"
        />

        @include('partials.json-template')
        @include('partials.word-formats')

        <x-ui.button type="submit" variant="primary" size="lg" block icon="plus">
            Créer {{ $kind->thisOne() }}
        </x-ui.button>
    </form>
</main>

</x-layouts.app>
