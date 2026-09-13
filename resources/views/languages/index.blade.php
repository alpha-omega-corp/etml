<x-layouts.app title="Que voulez-vous apprendre ?">

<main id="main" class="picker">

    <header class="picker__head">
        <h1 class="picker__title">Que voulez-vous <em>apprendre</em>&nbsp;?</h1>
        <p class="picker__lead">Vos cartes « je sais » vous suivent d'une langue à l'autre.</p>
    </header>

    @if ($languages->isEmpty())
        <x-ui.empty-state
            icon="layers"
            title="Aucune langue"
            message="Lancez `php artisan db:seed --class=LanguageSeeder` pour créer les langues."
        />
    @else
        <ul class="picker__grid">
            @foreach ($languages as $row)
                @php
                    $language = $row['language'];
                    $percent = $row['cards'] > 0 ? round($row['known'] / $row['cards'] * 100) : 0;
                @endphp

                <li class="picker__item">
                    <a href="{{ route('program.show', $language) }}" class="lang-card @if ($row['language']->id === $current) is-current @endif">
                        <span class="lang-card__head">
                            <span class="lang-card__code">{{ $language->shortLabel() }}</span>
                            <span class="lang-card__names">
                                <span class="lang-card__name">{{ $language->name }}</span>
                                <span class="lang-card__label">{{ $language->faceLabel() }}</span>
                            </span>
                        </span>

                        <span class="lang-card__figures">
                            <span class="lang-card__figure">
                                <span class="lang-card__number">{{ $row['cards'] }}</span>
                                <span class="lang-card__unit">cartes</span>
                            </span>
                            <span class="lang-card__figure">
                                <span class="lang-card__number">{{ $row['known'] }}</span>
                                <span class="lang-card__unit">connues</span>
                            </span>
                        </span>

                        <span class="lang-card__progress" role="img" aria-label="{{ $percent }} % connu">
                            <span class="lang-card__progress-fill" style="width: {{ $percent }}%"></span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

</main>

</x-layouts.app>
