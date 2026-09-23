<x-layouts.app :title="$language->name">

<main id="main" class="program">

    {{-- The trail replaces the page title: the language IS the last crumb, so
         it carries the <h1> rather than repeating itself above the trail. The
         pill beside it is the language naming itself — « Deutsch », not
         « Allemand ». --}}
    <div class="program__head">
        <x-ui.breadcrumbs
            heading
            :items="[
                ['label' => 'Accueil', 'href' => route('languages.index')],
                ['label' => $language->name],
            ]"
        />

        <x-ui.badge variant="accent">{{ $language->faceLabel() }}</x-ui.badge>
    </div>

    {{-- The programme: what is due, and when. --}}
    <section class="program__section" aria-labelledby="programme">
        <div class="program__section-head">
            <h2 class="program__section-title" id="programme">Programme</h2>

            @if ($isAdmin)
                <div class="cluster">
                    <x-ui.button type="button" size="sm" variant="primary" icon="plus" data-entry-new data-modal-open="entry">
                        Ajouter une date
                    </x-ui.button>

                    <a href="{{ route('program.edit', $language) }}" class="btn btn--sm">
                        <x-ui.icon name="file" size="14" />
                        <span class="btn__label">Coller le programme</span>
                    </a>
                </div>
            @endif
        </div>

        @if ($entries->isEmpty())
            <x-ui.empty-state
                icon="calendar"
                title="Aucune date"
                :message="$isAdmin
                    ? 'Collez le programme en JSON pour afficher les dates et ce qu\'il faut apprendre.'
                    : 'Le programme n\'a pas encore été saisi.'"
            />
        @else
            @php
                // La prochaine échéance : la première qui n'est pas derrière
                // nous. C'est la carte sur laquelle le carrousel s'ouvre ;
                // faute de quoi (année écoulée) il s'ouvre sur la dernière.
                $next = $entries->first(fn ($entry) => ! $entry->isPast());
                $start = $next ? $entries->search(fn ($entry) => $entry->is($next)) : $entries->count() - 1;
            @endphp

            <div
                class="carousel"
                x-data="carousel({ start: {{ $start }}, count: {{ $entries->count() }} })"
                x-bind:data-ready="ready"
                x-on:resize.window="measure()"
            >
                <button
                    type="button"
                    class="carousel__arrow carousel__arrow--prev"
                    x-on:click="prev()"
                    x-bind:disabled="atStart"
                    aria-controls="timeline"
                    aria-label="Date précédente"
                >
                    <x-ui.icon name="chevron-left" size="18" />
                </button>

                <div class="carousel__viewport">
                    <ol class="timeline" id="timeline" x-bind:style="track">
                @foreach ($entries as $entry)
                    <li
                        class="timeline__item @if ($entry->isPast()) is-past @elseif ($entry->isToday()) is-today @endif @if ($next && $entry->is($next)) is-next @endif"
                        data-entry
                        @if ($next && $entry->is($next)) data-timeline-next @endif
                        data-entry-id="{{ $entry->id }}"
                        data-entry-date="{{ $entry->date->toDateString() }}"
                        data-entry-title="{{ $entry->title }}"
                        data-entry-note="{{ $entry->note }}"
                        data-entry-units="{{ $entry->units->pluck('id')->join(',') }}"
                    >
                        <div class="timeline__date">
                            <span class="timeline__day">{{ $entry->date->translatedFormat('j') }}</span>
                            <span class="timeline__month">{{ $entry->date->translatedFormat('M') }}</span>
                        </div>

                        <div class="timeline__body">
                            <p class="timeline__title">
                                {{ $entry->title ?? 'À apprendre' }}

                                @if ($entry->isToday())
                                    <x-ui.badge variant="warning">Aujourd'hui</x-ui.badge>
                                @elseif (! $entry->isPast())
                                    <span class="timeline__away">dans {{ $entry->daysAway() }} j</span>
                                @endif
                            </p>

                            @if ($entry->note)
                                <p class="timeline__note">{{ $entry->note }}</p>
                            @endif

                            @if ($entry->units->isNotEmpty())
                                <div class="timeline__units">
                                    @foreach ($entry->units as $unit)
                                        <a
                                            href="{{ route('deck.show', [$language, $unit->kind->slug(), $unit]) }}"
                                            class="chip chip--{{ $unit->kind->value }}"
                                        >{{ $unit->name }}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($isAdmin)
                            @php
                                $label = $entry->title ?? $entry->date->translatedFormat('j F Y');
                            @endphp

                            <div class="timeline__actions">
                                <button
                                    type="button"
                                    class="timeline__action"
                                    data-entry-edit
                                    data-modal-open="entry"
                                    aria-label="Modifier « {{ $label }} »"
                                >
                                    <x-ui.icon name="pencil" size="14" />
                                </button>

                                <form
                                    method="POST"
                                    action="{{ route('entries.destroy', $entry) }}"
                                    onsubmit="return confirm('Retirer « {{ $label }} » du programme ?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="timeline__action timeline__action--danger" aria-label="Retirer « {{ $label }} »">
                                        <x-ui.icon name="trash" size="14" />
                                    </button>
                                </form>
                            </div>
                        @endif
                    </li>
                @endforeach
                    </ol>
                </div>

                <button
                    type="button"
                    class="carousel__arrow carousel__arrow--next"
                    x-on:click="next()"
                    x-bind:disabled="atEnd"
                    aria-controls="timeline"
                    aria-label="Date suivante"
                >
                    <x-ui.icon name="chevron-right" size="18" />
                </button>

                <p class="carousel__position" aria-live="polite" x-text="position"></p>
            </div>
        @endif
    </section>

    {{-- A subject that is read rather than drilled keeps notes; it only shows
         the kinds of deck it actually has. --}}
    @if ($notes->isNotEmpty())
        <section class="program__section" aria-labelledby="notes">
            <div class="program__section-head">
                <h2 class="program__section-title" id="notes">Fiches</h2>
            </div>

            <ul class="unit-list">
                @foreach ($notes as $note)
                    <li class="unit-list__item">
                        <a href="{{ route('notes.show', $note) }}" class="unit-card">
                            <span class="unit-card__name">{{ $note->title }}</span>
                            <span class="unit-card__count">{{ implode(' · ', $note->subjects) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @foreach ([[\App\Support\UnitKind::Vocabulary, $vocabulary], [\App\Support\UnitKind::Verbs, $verbs], [\App\Support\UnitKind::Selection, $selections]] as [$kind, $units])
        {{-- « Mes sélections » only appears once there is one: a selection is
             cut out of a chapter's word list, never started from here. --}}
        @continue (($kind->isPersonal() || $notes->isNotEmpty()) && $units->isEmpty())

        <section class="program__section" aria-labelledby="{{ $kind->value }}">
            <div class="program__section-head">
                <h2 class="program__section-title" id="{{ $kind->value }}">{{ $kind->heading() }}</h2>

                @unless ($kind->isPersonal())
                    <a href="{{ route('units.create', ['language' => $language->slug, 'kind' => $kind->slug()]) }}" class="btn btn--sm">
                        <x-ui.icon name="plus" size="14" />
                        <span class="btn__label">{{ $kind->newLabel() }}</span>
                    </a>
                @endunless
            </div>

            @if ($units->isEmpty())
                <p class="program__empty">{{ $kind->none() }} pour l'instant.</p>
            @else
                <ul class="unit-list">
                    @foreach ($units as $unit)
                        @php
                            $done = $known[$unit->id] ?? 0;
                            $percent = $unit->cards_count > 0 ? round($done / $unit->cards_count * 100) : 0;
                        @endphp

                        <li class="unit-list__item">
                            <a href="{{ route('deck.show', [$language, $kind->slug(), $unit]) }}" class="unit-card">
                                <span class="unit-card__name">{{ $unit->name }}</span>

                                <span class="unit-card__count">
                                    @if ($unit->cards_count === 0)
                                        à remplir
                                    @else
                                        {{ $done }} / {{ $unit->cards_count }}
                                    @endif
                                </span>

                                <span class="unit-card__progress">
                                    <span class="unit-card__progress-fill" style="width: {{ $percent }}%"></span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endforeach

    @if ($isAdmin)
        @php
            // A rejected save comes back here: the form reopens on what was
            // sent, still pointed at the line it was editing.
            $failed = $errors->hasAny(['date', 'title', 'note', 'units', 'entry_id']);
            $chosen = array_map('intval', (array) old('units', []));
        @endphp

        <x-ui.modal id="entry" title="Date du programme" :data-entry-reopen="$failed ? '1' : null">
            <form id="entry-form" method="POST" action="{{ route('entries.save') }}" class="stack" data-entry-form>
                @csrf
                <input type="hidden" name="language" value="{{ $language->slug }}">
                <input type="hidden" name="entry_id" value="{{ old('entry_id') }}" data-entry-field="id">

                <x-ui.form.input
                    name="date"
                    type="date"
                    label="Date"
                    required
                    data-entry-field="date"
                />

                <x-ui.form.input
                    name="title"
                    label="Intitulé"
                    placeholder="Test 1"
                    hint="Laissé vide, la ligne s'affiche « À apprendre »."
                    data-entry-field="title"
                />

                <x-ui.form.input
                    name="note"
                    label="Remarque"
                    placeholder="Vacances d'automne, du 12.10 au 25.10"
                    data-entry-field="note"
                />

                <x-ui.form.field
                    name="units"
                    label="À apprendre pour cette date"
                    :hint="$vocabulary->isEmpty() && $verbs->isEmpty()
                        ? 'Créez d\'abord un chapitre ou une page.'
                        : null"
                >
                    @foreach ([[\App\Support\UnitKind::Vocabulary, $vocabulary], [\App\Support\UnitKind::Verbs, $verbs]] as [$kind, $units])
                        @if ($units->isNotEmpty())
                            <p class="entry-units__heading">{{ $kind->heading() }}</p>

                            <div class="entry-units">
                                @foreach ($units as $unit)
                                    <label class="choice">
                                        <input
                                            type="checkbox"
                                            class="choice__control"
                                            name="units[]"
                                            value="{{ $unit->id }}"
                                            data-entry-unit
                                            @checked(in_array($unit->id, $chosen, true))
                                        >
                                        <span class="choice__text">
                                            <span class="choice__label">{{ $unit->name }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </x-ui.form.field>
            </form>

            <x-slot:footer>
                <x-ui.button type="button" variant="ghost" data-modal-close>Annuler</x-ui.button>
                <x-ui.button type="submit" form="entry-form" variant="primary" icon="check" data-entry-submit>
                    Enregistrer
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif

</main>

</x-layouts.app>
