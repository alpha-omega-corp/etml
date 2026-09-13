{{--
    The two shapes `App\Support\WordList` accepts, written in the language the
    deck belongs to. Shown next to every word-list field.
--}}
<details class="json-template">
    <summary>Formats acceptés</summary>
    <div class="json-template__body">
        <p>Un objet <strong>français → {{ mb_strtolower($language->name) }}</strong> :</p>
        <pre class="json-template__code">{{ $sampleMap }}</pre>
        <p>Ou une liste, quand une entrée porte un exemple ou un intertitre :</p>
        <pre class="json-template__code">{{ $sampleList }}</pre>
        <p>{{ \App\Support\WordList::MAX_ENTRIES }} entrées au maximum.</p>
    </div>
</details>
