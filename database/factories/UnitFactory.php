<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Unit;
use App\Support\UnitKind;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
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
            'kind' => UnitKind::Vocabulary,
            'name' => ucfirst(fake()->words(3, true)),
            'position' => fake()->unique()->numberBetween(1, 999),
        ];
    }

    /**
     * A page of verbs rather than a vocabulary chapter.
     */
    public function verbs(): static
    {
        return $this->state(['kind' => UnitKind::Verbs]);
    }
}
