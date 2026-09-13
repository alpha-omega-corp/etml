@php
    $sample = <<<'JSON'
        [
          {"date": "2026-09-22", "test": "Test 1",
           "chapters": ["I. Der Mensch — Personalien & Familie"],
           "pages": ["Verbes forts, p. 12"]},
          {"date": "2026-10-06", "test": "Test 2",
           "chapters": ["II. Die Wohnung"]}
        ]
        JSON;
@endphp

<x-layouts.app :title="'Programme · '.$language->name">

<main id="main" class="page-form">
    <a href="{{ route('program.show', $language) }}" class="page-form__back">
        <x-ui.icon name="arrow-left" size="16" />
        Retour à {{ $language->name }}
    </a>

    <h1 class="page-form__title">Programme · {{ $language->name }}</h1>
    <p class="page-form__lead">
        Une liste de dates, en JSON. Chaque date porte le nom du test et les
        chapitres et pages de verbes qu'il couvre. Un nom qui n'existe pas
        encore crée le chapitre ou la page, vide, prêt à être rempli.
    </p>

    <form method="POST" action="{{ route('program.store', $language) }}" class="stack">
        @csrf

        <x-ui.form.textarea
            name="program"
            label="Programme (JSON)"
            rows="16"
            required
            :value="$current"
            :placeholder="$sample"
            hint="Enregistrer remplace tout le programme de cette langue."
        />

        <details class="json-template">
            <summary>Format attendu</summary>
            <div class="json-template__body">
                <pre class="json-template__code" id="programSample">{{ $sample }}</pre>
                <p>
                    Seule « date » est obligatoire, au format 2026-09-22.
                    « test » nomme l'échéance, « chapters » liste les chapitres de
                    vocabulaire, « pages » les pages de verbes, « note » ajoute une
                    remarque. {{ \App\Support\ProgramList::MAX_ENTRIES }} dates au maximum.
                </p>

                <x-ui.button type="button" variant="default" size="sm" icon="file" data-copy="programSample">
                    Copier l'exemple
                </x-ui.button>
            </div>
        </details>

        <x-ui.button type="submit" variant="primary" size="lg" block icon="check">
            Enregistrer le programme
        </x-ui.button>
    </form>

    @if ($current !== '')
        <form method="POST" action="{{ route('program.destroy', $language) }}" class="page-form__danger"
              onsubmit="return confirm('Effacer tout le programme de {{ $language->name }} ?')">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger" size="sm" icon="trash">
                Effacer le programme
            </x-ui.button>
        </form>
    @endif
</main>

</x-layouts.app>
