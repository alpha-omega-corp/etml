<!DOCTYPE html>
<html lang="fr">
<head>
    @include('partials.head', ['title' => 'Nouveau chapitre'])
</head>
<body class="app-body">

<header class="deck-bar">
    <span class="deck-bar__title">MTU</span>

    <div class="cluster push-end" style="--cluster-gap: 0">
        <x-ui.theme-toggle />
    </div>
</header>

<main id="main" class="page-form">
    <a href="{{ route('cards') }}" class="page-form__back">
        <x-ui.icon name="arrow-left" size="16" />
        Retour aux cartes
    </a>

    <h1 class="page-form__title">Nouveau chapitre</h1>
    <p class="page-form__lead">
        Un chapitre se crée à partir de sa liste de mots, en JSON. Collez-la
        ci-dessous : les cartes sont créées avec le chapitre.
    </p>

    <form method="POST" action="{{ route('chapters.store') }}" class="stack">
        @csrf

        <x-ui.form.input name="name" label="Nom du chapitre" placeholder="II. Die Wohnung" required />

        <x-ui.form.textarea
            name="words"
            label="Mots (JSON)"
            rows="14"
            required
            :placeholder="$wordsPlaceholder"
            hint="Un objet français vers allemand, ou une liste d'objets fr / de / ex / sec."
        />

        @include('partials.json-template')

        <details class="json-template">
            <summary>Formats acceptés</summary>
            <div class="json-template__body">
                <p>Un objet <strong>français → allemand</strong> :</p>
                <pre class="json-template__code">{{ $sampleMap }}</pre>
                <p>Ou une liste, quand une entrée porte un exemple ou un intertitre :</p>
                <pre class="json-template__code">{{ $sampleList }}</pre>
                <p>{{ \App\Support\WordList::MAX_ENTRIES }} entrées au maximum.</p>
            </div>
        </details>

        <x-ui.button type="submit" variant="primary" size="lg" block icon="plus">
            Créer le chapitre
        </x-ui.button>
    </form>
</main>

@include('partials.flash')

<script>
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;

    const source = document.getElementById(button.dataset.copy);
    if (!source) return;

    navigator.clipboard.writeText(source.textContent).then(() => {
        const label = button.querySelector('.btn__label') ?? button;
        const original = label.textContent;
        label.textContent = 'Copié';
        setTimeout(() => { label.textContent = original; }, 1600);
    }).catch(() => {});
});
</script>

</body>
</html>
