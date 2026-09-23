<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Note;
use Illuminate\Database\Seeder;

/**
 * The revision notes that ship with the app. A note is found by its subject
 * and position, so editing a file and re-running the seeder updates it in
 * place: `php artisan db:seed --class=NoteSeeder`.
 */
class NoteSeeder extends Seeder
{
    /**
     * @var array<int, array{language: string, position: int, title: string, subjects: array<int, string>, file: string}>
     */
    private const NOTES = [
        [
            'language' => 'histoire',
            'position' => 1,
            'title' => 'Suisse et industrialisation',
            'subjects' => ['Histoire suisse', 'Industrialisation'],
            'file' => 'note-suisse-industrialisation.html',
        ],
    ];

    public function run(): void
    {
        foreach (self::NOTES as $note) {
            $language = Language::where('slug', $note['language'])->first();

            if ($language === null) {
                $this->command?->warn("Matière « {$note['language']} » absente, « {$note['title']} » ignorée.");

                continue;
            }

            Note::updateOrCreate(
                ['language_id' => $language->id, 'position' => $note['position']],
                [
                    'title' => $note['title'],
                    'subjects' => $note['subjects'],
                    'html' => file_get_contents(database_path("seeders/data/{$note['file']}")),
                ],
            );

            $this->command?->info("Fiche « {$note['title']} » ({$language->name}).");
        }
    }
}
