# MTU — Vocabulaire

Cartes de vocabulaire allemand → français, portées par Laravel + PostgreSQL.

Le contenu est rangé en deux niveaux : **branches** (`branches`) puis
**chapitres** (`chapters`). Les neuf branches sont créées par le seeder — Algèbre,
Géométrie, Physique, Chimie, Économie, Histoire, Français, Allemand, Anglais — et
le chapitre importé, « I. Der Mensch — Personalien & Familie » (146 mots), vit
sous Allemand.
Chaque utilisateur ouvre une session avec son seul nom, et ses cartes « je sais »
/ « à revoir » (`card_states`) le suivent d'une visite à l'autre.

## Navigation

La colonne de gauche liste les chapitres et leur nombre de mots ; un filet la
sépare du contenu, et un fil d'Ariane rappelle le chapitre ouvert.

Sur téléphone c'est un tiroir latéral classique : une icône hamburger dans la
barre du haut, un panneau qui glisse depuis la gauche par-dessus un voile, et une
fermeture par le bouton ×, Échap, le voile, ou l'ouverture d'un chapitre. Le
fond ne défile plus tant que le tiroir est ouvert, et le focus entre dans le
panneau puis revient sur le hamburger.

Un chapitre se renomme sur place : au survol de sa ligne un crayon apparaît, le
nom devient un champ, et Entrée ou la sortie du champ enregistre (Échap annule).
`PATCH /chapters/{chapter}` répond en JSON quand la requête l'attend, si bien que
la page n'est pas rechargée.

Le chapitre courant vit dans la session. Les chapitres appartiennent encore à une
branche en base (`branches`), ce qui ordonne la liste, mais il n'y a plus de
navigation par branche : tous les chapitres sont listés ensemble.

La création d'un chapitre a sa propre page (`/chapters/create`, « + Nouveau
chapitre » dans la colonne). On y colle la liste de mots en **JSON**, et la page
porte aussi un **modèle de consigne à copier dans Claude** : joignez-y les photos
de vos pages, Claude renvoie le JSON, vous le collez dans le champ. Le modèle est
dans `resources/views/partials/json-template.blade.php` ; un test vérifie que
l'exemple qu'il montre repasse bien dans le lecteur.

Deux formes sont acceptées — un objet français → allemand :

```json
{"la maison": "das Haus, ¨er", "la cuisine": "die Küche, n"}
```

ou une liste, quand une entrée porte un exemple ou un intertitre :

```json
[{"fr": "la maison", "de": "das Haus, ¨er", "ex": "zu Hause", "sec": "Die Wohnung"}]
```

Le découpage vit dans `App\Support\WordList`, plafonné à 1000 entrées. Un
chapitre laissé vide affiche le même éditeur pour être rempli après coup ; un
chapitre qui contient déjà des mots refuse l'import, pour qu'un jeu ne soit
jamais doublé.

## Le paquet de cartes

`database/seeders/data/deck.json` contient les mots (`sec`, `de`, `fr`, `ex`).
Le seeder est idempotent (upsert sur `position`) : modifiez le JSON puis
relancez `php artisan db:seed --class=CardSeeder`.
