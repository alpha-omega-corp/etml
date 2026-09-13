<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Language;
use App\Models\Unit;
use App\Support\UnitKind;
use App\Support\WordList;
use Illuminate\Database\Seeder;

class CardSeeder extends Seeder
{
    /**
     * The decks that ship with the app, in printed order.
     *
     * A unit is found by its language, kind and position, which is also what
     * the upsert keys on: edit a file, re-run the seeder, and the cards are
     * updated in place rather than doubled. The names match the ones the
     * programmes use, so `ProgramSeeder` links its dates to these units
     * instead of creating empty twins.
     *
     * @var array<int, array{language: string, kind: UnitKind, position: int, name: string, file: string}>
     */
    private const DECKS = [
        ['language' => 'allemand', 'kind' => UnitKind::Vocabulary, 'position' => 1, 'name' => 'Voc 1', 'file' => 'deck.json'],
        ['language' => 'allemand', 'kind' => UnitKind::Vocabulary, 'position' => 2, 'name' => 'Voc 2', 'file' => 'voc-2.json'],
        ['language' => 'allemand', 'kind' => UnitKind::Verbs, 'position' => 1, 'name' => 'Verben 1', 'file' => 'verben-1.json'],
        ['language' => 'allemand', 'kind' => UnitKind::Verbs, 'position' => 2, 'name' => 'Verben 2', 'file' => 'verben-2.json'],
        ['language' => 'anglais', 'kind' => UnitKind::Vocabulary, 'position' => 1, 'name' => 'Voc Unit 1', 'file' => 'voc-unit-1-en.json'],
    ];

    public function run(): void
    {
        $languages = Language::whereIn('slug', array_column(self::DECKS, 'language'))->get()->keyBy('slug');

        foreach (self::DECKS as $deck) {
            $language = $languages->get($deck['language']);

            if ($language === null) {
                $this->command?->warn("Langue « {$deck['language']} » absente, « {$deck['name']} » ignoré.");

                continue;
            }

            $unit = Unit::firstOrCreate(
                ['language_id' => $language->id, 'kind' => $deck['kind'], 'position' => $deck['position']],
                ['name' => $deck['name']],
            );

            $rows = $this->read($deck['file'], $language->code);
            $now = now();

            Card::upsert(
                array_map(fn (array $row, int $i) => [
                    'unit_id' => $unit->id,
                    'section' => $row['section'],
                    'term' => $row['term'],
                    'translation' => $row['translation'],
                    'example' => $row['example'],
                    'position' => $i,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $rows, array_keys($rows)),
                ['unit_id', 'position'],
                ['section', 'term', 'translation', 'example', 'updated_at'],
            );

            $this->command?->info(count($rows)." mots dans « {$unit->name} » ({$language->name}).");
        }
    }

    /**
     * Every file goes through the parser the paste form uses, keyed on the
     * language's own code — `de` for German, `en` for English — so a deck that
     * ships is read exactly like one a user pastes in.
     *
     * @return array<int, array{section: string|null, term: string, translation: string, example: string|null}>
     */
    private function read(string $file, string $code): array
    {
        return WordList::parse(file_get_contents(database_path("seeders/data/{$file}")), $code);
    }
}
