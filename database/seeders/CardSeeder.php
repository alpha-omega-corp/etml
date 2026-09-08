<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Card;
use App\Models\Chapter;
use Illuminate\Database\Seeder;

class CardSeeder extends Seeder
{
    /**
     * Import the first unit's vocabulary, preserving its original order.
     */
    public function run(): void
    {
        $branch = Branch::where('slug', 'allemand')->firstOrFail();

        $chapter = Chapter::firstOrCreate(
            ['branch_id' => $branch->id, 'position' => 1],
            ['name' => 'I. Der Mensch — Personalien & Familie'],
        );

        $path = database_path('seeders/data/deck.json');

        /** @var array<int, array{sec: string, de: string, fr: string, ex?: string}> $deck */
        $deck = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $now = now();

        $rows = [];
        foreach ($deck as $i => $entry) {
            $rows[] = [
                'chapter_id' => $chapter->id,
                'section' => $entry['sec'],
                'de' => $entry['de'],
                'fr' => $entry['fr'],
                'example' => $entry['ex'] ?? null,
                'position' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Card::upsert($rows, ['chapter_id', 'position'], ['section', 'de', 'fr', 'example', 'updated_at']);
    }
}
