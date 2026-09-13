# MTU — Cartes de langues

Un jeu de cartes pour apprendre une langue, porté par Laravel + PostgreSQL.
Le jeu lui-même est un **module** : il ne sait rien de la langue qu'il fait
réviser. Ajouter une langue, c'est ajouter une ligne.

## Le modèle

```
langue (languages)          Allemand (de / Deutsch), Anglais (en / English)
 ├─ programme               une liste de dates : un test, et ce qu'il couvre
 ├─ vocabulaire → chapitres ┐
 └─ verbes      → pages     ┴ des « units », qui portent les cartes
```

- **`languages`** — `name` (Allemand), `slug` (allemand), `code` (de, la clé
  qu'une liste collée peut utiliser) et `label` (Deutsch, le titre au dos de la
  carte).
- **`units`** — un paquet de cartes, de `kind` `vocabulary` (un chapitre) ou
  `verbs` (une page). Les deux jouent exactement le même jeu ; seul le mot
  employé change, et c'est l'énumération `App\Support\UnitKind` qui le décide,
  accords en français compris.
- **`cards`** — `term` (le mot dans la langue apprise) et `translation` (le
  français), plus l'exemple et l'intertitre. Aucun nom de langue dans le
  schéma : c'est ce qui rend le jeu réutilisable.
- **`program_entries`** — une date, un intitulé, et les units qu'elle couvre
  (`program_entry_unit`). Partagé par tous ceux qui apprennent cette langue.
- **`card_states`** — « je sais » / « à revoir », par utilisateur et par carte.

Chaque utilisateur ouvre une session avec son seul nom, et ses cartes le
suivent d'une visite à l'autre, dans toutes les langues.

## Navigation

```
/                            les langues, avec l'avancement et la prochaine date
/allemand                    le programme, puis les chapitres et les pages
/allemand/vocabulaire/3      le jeu de cartes
/allemand/verbes/7           le même jeu, sur une page de verbes
```

`/{language}` avale n'importe quel premier segment : dans `routes/web.php`,
toute route à premier segment fixe doit rester **au-dessus** de ce bloc.

Sur le jeu, la colonne de gauche liste les units de la même sorte et ramène à
la langue. Sur téléphone c'est un tiroir : une icône hamburger, un panneau qui
glisse par-dessus un voile, une fermeture par ×, Échap, le voile, ou
l'ouverture d'une unit.

Un chapitre ou une page se renomme sur place : au survol, un crayon remplace le
nom par un champ ; Entrée ou la sortie du champ enregistre, Échap annule.
`PATCH /units/{unit}` répond en JSON quand la requête l'attend, si bien que la
page n'est pas rechargée.

## Le module de cartes

Deux fichiers, et rien d'autre à connaître :

- `resources/views/components/deck/game.blade.php` — le plateau, rendu par
  `<x-deck.game :language :unit :cards :states />`. Il termine par un bloc
  `<script type="application/json" id="deck-data">`.
- `resources/js/components/deck.js` — tout le comportement : retournement,
  ordre, filtres, marquage, confettis. Il lit `#deck-data` et ne sait rien
  d'autre.

Le contrat entre les deux :

```json
{
  "cards":  [{"id": 1, "term": "das Haus", "translation": "la maison", "ex": null}],
  "states": {"1": "known"},
  "labels": {"native": "Français", "target": "Deutsch"},
  "statusUrl": "/cards/__ID__/status"
}
```

Échangez `term` et `labels.target`, et le même module enseigne une autre
langue. Les autres comportements de page — le tiroir, le renommage, la copie,
« effacer mes listes » — sont des modules frères dans `resources/js/components`,
tous branchés par délégation sur `document`.

## Les listes de mots

Une unit se crée à partir de sa liste, en JSON, sur `/units/create`. La page
porte aussi un **modèle de consigne à copier dans Claude**, écrit dans la
langue du paquet : joignez-y les photos de vos pages, Claude renvoie le JSON,
vous le collez dans le champ.

Deux formes sont acceptées — un objet français → langue :

```json
{"la maison": "das Haus, ¨er", "la cuisine": "die Küche, n"}
```

ou une liste, quand une entrée porte un exemple ou un intertitre. Le côté
étranger est keyé par le code de la langue :

```json
[{"fr": "la maison", "de": "das Haus, ¨er", "ex": "zu Hause", "sec": "Die Wohnung"}]
[{"fr": "la maison", "en": "the house",     "ex": "at home",  "sec": "The flat"}]
```

Le découpage vit dans `App\Support\WordList`, plafonné à 1000 entrées. Une unit
laissée vide affiche le même éditeur pour être remplie après coup ; une unit qui
contient déjà des mots refuse l'import, pour qu'un jeu ne soit jamais doublé.

## Le programme

Deux façons de le tenir, toutes deux réservées au mode administrateur.

**À la main**, depuis `/{language}` : « Ajouter une date » ouvre un formulaire
— date, intitulé, remarque, et les chapitres et pages cochés dans la liste —
et chaque ligne du calendrier porte au survol un crayon qui rouvre ce même
formulaire sur ses valeurs, et une corbeille qui la retire. Retirer une date ne
touche pas aux chapitres qu'elle désignait.

**En bloc**, en collant du JSON sur `/{language}/programme`. Il remplace le
programme en place, et le formulaire s'ouvre sur celui déjà enregistré.

```json
[
  {"date": "2026-09-22", "test": "Test 1",
   "chapters": ["I. Der Mensch — Personalien & Familie"],
   "pages": ["Verbes forts, p. 12"]},
  {"date": "2026-10-06", "test": "Test 2", "chapters": ["II. Die Wohnung"]}
]
```

Seule `date` est obligatoire. Les units sont désignées **par leur nom** : un nom
qui n'existe pas encore crée le chapitre ou la page, vide, prêt à être rempli —
un programme peut donc arriver avant les listes de mots. Le découpage vit dans
`App\Support\ProgramList`, l'écriture dans `App\Support\ProgramImporter`, que
le formulaire et le seeder partagent.

Le programme d'une année se range aussi en fichier, dans
`database/seeders/data/` — `program-allemand-2026-2027.json` porte les dates du
« Deutsches Lernprogramm 2026-2027 MTU1B », `program-anglais-2026-2027.json`
la colonne rouge du programme d'anglais (ses tests et leurs corrections). Seules y figurent celles qui portent
quelque chose à apprendre : les vacances, les blocages de notes et les examens
des autres branches sont sur la feuille de classe, pas ici. Le fichier n'est pas
dans `DatabaseSeeder` (c'est le calendrier d'une classe, pas une donnée dont
l'application a besoin) :

```
php artisan db:seed --class=ProgramSeeder
```

Relancer remplace le programme en place, sans doubler ni les dates ni les
chapitres qu'elles nomment.

## Les paquets livrés

Les chapitres de vocabulaire s'appellent **« Voc 1 », « Voc 2 »…**, numérotés
dans l'ordre des semaines du programme ; la tranche du manuel que chacun
couvre (« Chapitre 2.2 → 3.1 ») vit dans la note de sa date, pas dans son nom.

`database/seeders/data/` porte leurs mots, au format que lit
`App\Support\WordList` :

| fichier | langue | unité | sorte | cartes |
| --- | --- | --- | --- | --- |
| `deck.json` | Allemand | Voc 1 | vocabulaire | 146 |
| `voc-2.json` | Allemand | Voc 2 | vocabulaire | 179 |
| `verben-1.json` | Allemand | Verben 1 | verbes | 61 |
| `verben-2.json` | Allemand | Verben 2 | verbes | 61 |
| `voc-unit-1-en.json` | Anglais | Voc Unit 1 | vocabulaire | 123 |

Seuls les mots **surlignés** dans le manuel y figurent ; le reste de la page
n'est pas repris.

Une page de verbes est une carte comme une autre : l'infinitif au recto, la
traduction au verso, et les trois formes (présent, prétérit, parfait) en
exemple — exactement la forme que les verbes irréguliers prennent déjà dans les
listes de vocabulaire.

`CardSeeder` retrouve chaque unité par sa **langue, sa sorte et sa position**, et l'upsert
porte sur `(unit_id, position)` : modifiez un fichier, relancez
`php artisan db:seed --class=CardSeeder`, et les cartes sont mises à jour sur
place plutôt que doublées. Ajouter un paquet livré, c'est un fichier et une
ligne dans `CardSeeder::DECKS`.
