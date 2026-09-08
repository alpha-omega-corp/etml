# Der Mensch — Vocabulaire

Cartes de vocabulaire allemand → français (I. Der Mensch : Personalien, Familie,
Charakter, Verhalten, Wollen, Handeln), portées par Laravel + PostgreSQL.

Les 146 mots vivent en base (`cards`). Chaque utilisateur ouvre une session avec
son seul nom, et ses cartes « je sais » / « à revoir » (`card_states`) le
suivent d'une visite à l'autre.

## Démarrage

```bash
docker compose up -d          # PostgreSQL 17 sur le port 5436
php artisan migrate --seed    # schéma + import du vocabulaire
php artisan serve             # http://127.0.0.1:8000
```

Entrez un nom d'utilisateur : il est créé au premier passage et retrouvé ensuite.

## Tests

```bash
php artisan test
```

Les tests utilisent la base `card_test` du même conteneur ; créez-la une fois
avec `docker compose exec db createdb -U card card_test`.

## Le paquet de cartes

`database/seeders/data/deck.json` contient les mots (`sec`, `de`, `fr`, `ex`).
Le seeder est idempotent (upsert sur `position`) : modifiez le JSON puis
relancez `php artisan db:seed --class=CardSeeder`.
