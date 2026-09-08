<x-layouts.blank title="Connexion">
    <x-ui.card>
        <form method="POST" action="{{ route('login') }}" class="stack">
            @csrf

            <x-ui.form.input
                name="username"
                label="Votre nom"
                autocomplete="username"
                autofocus
                required
                hint="Sans mot de passe — il retrouve vos cartes."
            />

            <x-ui.button type="submit" variant="primary" size="lg" block trailing-icon="arrow-right">
                Commencer
            </x-ui.button>
        </form>
    </x-ui.card>
</x-layouts.blank>
