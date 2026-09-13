<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\ProgramEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramEntry>
 */
class ProgramEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'language_id' => Language::factory(),
            'date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'title' => 'Test '.fake()->numberBetween(1, 9),
            'note' => null,
        ];
    }
}
