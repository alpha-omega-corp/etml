@php
    /**
     * The prompt the user pastes into Claude alongside photos of their
     * textbook pages. It asks for exactly the shape `App\Support\WordList`
     * reads back, in whichever language the deck belongs to.
     */
    $subject = $kind === \App\Support\UnitKind::Verbs
        ? 'de pages de verbes '.mb_strtolower($language->name).' / français'
        : 'de pages de vocabulaire '.mb_strtolower($language->name).' / français';

    $code = $language->code;

    // The format is shown with the language's own sample entry, so the prompt
    // never illustrates German to someone learning English.
    $example = $sampleList;

    $claudePrompt = <<<TXT
        Voici des photos {$subject}.

        Transcris chaque entrée en JSON. Ne traduis rien et ne corrige rien :
        - copie les deux colonnes telles qu'imprimées, avec les articles, les marques
          de pluriel (", en", ", ¨er", ", -"), les astérisques des verbes irréguliers
          et les notes entre parenthèses comme "(Pl.)" ou "(un-)" ;
        - garde l'ordre imprimé, sans trier, fusionner ni dédoublonner ;
        - garde les accents et les caractères spéciaux : ä ö ü ß é è ê à ç œ ¨ → — ;
        - "ex" reçoit la phrase d'exemple, la liste de conjugaison ou la note imprimée
          avec l'entrée ; null s'il n'y en a pas ;
        - "sec" reçoit l'intertitre sous lequel l'entrée se trouve, répété pour chaque
          entrée de la section ; null si la page n'a pas d'intertitres ;
        - si un mot est coupé, flou ou illisible, omets l'entrée plutôt que de deviner.

        Réponds uniquement avec le JSON, sans texte autour, à ce format exact,
        où "fr" porte le français et "{$code}" le mot étranger :

        {$example}
        TXT;
@endphp

<details class="json-template">
    <summary>Modèle à coller dans Claude</summary>

    <div class="json-template__body">
        <p class="json-template__lead">
            Ouvrez Claude, joignez les photos de vos pages et collez ce texte. Le JSON
            qu'il renvoie se colle tel quel dans le champ ci-dessus.
        </p>

        <pre class="json-template__code" id="claudePrompt">{{ $claudePrompt }}</pre>

        <x-ui.button
            type="button"
            variant="default"
            size="sm"
            icon="file"
            data-copy="claudePrompt"
        >Copier le modèle</x-ui.button>
    </div>
</details>
