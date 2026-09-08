<x-layouts.blank title="Administrateur">
    <x-ui.card>
        @if (! $enabled)
            <x-ui.alert variant="warning" title="Non configuré">
                Renseignez <code>ADMIN_PASSWORD</code> dans <code>.env</code> pour
                activer le mode administrateur.
            </x-ui.alert>
        @endif

        <form method="POST" action="{{ route('admin.login') }}" class="stack">
            @csrf

            <x-ui.form.input
                name="password"
                type="password"
                label="Mot de passe administrateur"
                autocomplete="current-password"
                autofocus
                required
                hint="Permet de supprimer des chapitres."
            />

            <x-ui.button type="submit" variant="primary" size="lg" block icon="lock">
                Activer
            </x-ui.button>
        </form>

        <p class="mt-4 text-muted" style="font-size: var(--text-sm)">
            <a href="{{ route('cards') }}">Retour aux cartes</a>
        </p>
    </x-ui.card>
</x-layouts.blank>
