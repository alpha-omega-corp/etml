<?php

namespace Database\Seeders;

use App\Models\Card;
use Illuminate\Database\Seeder;

class CardSeeder extends Seeder
{
    /**
     * Import the vocabulary deck, preserving its original order.
     */
    public function run(): void
    {
        $path = database_path('seeders/data/deck.json');

        /** @var array<int, array{sec: string, de: string, fr: string, ex?: string}> $deck */
        $deck = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $now = now();

        $rows = [];
        foreach ($deck as $i => $entry) {
            $rows[] = [
                'section' => $entry['sec'],
                'de' => $entry['de'],
                'fr' => $entry['fr'],
                'example' => $entry['ex'] ?? null,
                'position' => $i,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Card::upsert($rows, ['position'], ['section', 'de', 'fr', 'example', 'updated_at']);
    }
}
