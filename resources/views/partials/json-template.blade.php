@php
    /**
     * The prompt the user pastes into Claude alongside photos of their textbook
     * pages. It asks for exactly the shape `App\Support\WordList` reads back.
     */
    $claudePrompt = <<<'TXT'
        Voici des photos de pages de vocabulaire allemand / français.

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

        Réponds uniquement avec le JSON, sans texte autour, à ce format exact :

        [
          {"fr": "l'homme, l'être humain", "de": "der Mensch, en, en", "ex": null, "sec": "Personalien"},
          {"fr": "la mère", "de": "die Mutter, ¨", "ex": null, "sec": "Familie"},
          {"fr": "mourir (de)", "de": "*sterben (an + D)", "ex": "stirbt, starb, ist gestorben", "sec": "Personalien"}
        ]
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
