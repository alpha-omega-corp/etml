<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * The languages the app offers. Order is the order they appear on the home
     * page; adding one here is all it takes for it to have its own chapters,
     * verb pages and programme.
     *
     * @var array<string, array{name: string, code: string, label: string}>
     */
    public const LANGUAGES = [
        'allemand' => ['name' => 'Allemand', 'code' => 'de', 'label' => 'Deutsch'],
        'anglais' => ['name' => 'Anglais', 'code' => 'en', 'label' => 'English'],
        'histoire' => ['name' => 'Histoire', 'code' => 'hist', 'label' => 'Fiches de révision'],
    ];

    public function run(): void
    {
        $position = 1;

        foreach (self::LANGUAGES as $slug => $language) {
            Language::updateOrCreate(
                ['slug' => $slug],
                $language + ['position' => $position++],
            );
        }
    }
}
