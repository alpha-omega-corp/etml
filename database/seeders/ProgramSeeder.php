<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Support\ProgramImporter;
use App\Support\ProgramList;
use Illuminate\Database\Seeder;

/**
 * The school programmes, in the same JSON an administrator would paste.
 *
 * Runs after `CardSeeder` so its dates link to the units the decks created
 * instead of creating empty twins. Re-running replaces the dates it owns, so
 * it is safe on its own too: `php artisan db:seed --class=ProgramSeeder`.
 */
class ProgramSeeder extends Seeder
{
    /**
     * Language slug => the file holding its programme.
     *
     * @var array<string, string>
     */
    private const PROGRAMS = [
        'allemand' => 'program-allemand-2026-2027.json',
        'anglais' => 'program-anglais-2026-2027.json',
    ];

    public function run(): void
    {
        foreach (self::PROGRAMS as $slug => $file) {
            $language = Language::where('slug', $slug)->first();

            if ($language === null) {
                $this->command?->warn("Langue « {$slug} » absente, programme ignoré.");

                continue;
            }

            $rows = ProgramList::parse(file_get_contents(database_path("seeders/data/{$file}")));

            // Replaces whatever was there, so re-running is safe.
            ProgramImporter::apply($language, $rows);

            $this->command?->info(count($rows)." dates importées pour « {$language->name} ».");
        }
    }
}
