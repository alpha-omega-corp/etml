<?php

namespace Database\Factories;

use App\Models\Card;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unit_id' => Unit::factory(),
            'section' => null,
            'term' => fake()->word(),
            'translation' => fake()->word(),
            'example' => null,
            'position' => fake()->unique()->numberBetween(0, 9999),
        ];
    }

    /**
     * A card is played through its unit's list, so a made-up card joins the
     * list of the unit that wrote it.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Card $card) => $card->units()->syncWithoutDetaching([
            $card->unit_id => ['position' => $card->position],
        ]));
    }
}
