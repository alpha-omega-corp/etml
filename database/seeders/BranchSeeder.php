<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * The subjects the app is organised by. Order is the order they appear in
     * the sidebar.
     */
    public const BRANCHES = [
        'algebre' => 'Algèbre',
        'geometrie' => 'Géométrie',
        'physique' => 'Physique',
        'chimie' => 'Chimie',
        'economie' => 'Économie',
        'histoire' => 'Histoire',
        'francais' => 'Français',
        'allemand' => 'Allemand',
        'anglais' => 'Anglais',
    ];

    public function run(): void
    {
        $position = 1;

        foreach (self::BRANCHES as $slug => $name) {
            Branch::updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'position' => $position++],
            );
        }
    }
}
