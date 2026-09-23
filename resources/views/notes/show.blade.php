<x-layouts.app :title="$note->title">

<main id="main" class="note">

    <div class="program__head">
        <x-ui.breadcrumbs
            heading
            :items="[
                ['label' => 'Accueil', 'href' => route('languages.index')],
                ['label' => $note->language->name, 'href' => route('program.show', $note->language)],
                ['label' => $note->title],
            ]"
        />
    </div>

    <iframe class="note__page" data-note-frame src="{{ route('notes.page', $note) }}" title="{{ $note->title }}"></iframe>

</main>

</x-layouts.app>
