<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = ucfirst(fake()->words(3, true));

        return [
            'language_id' => Language::factory(),
            'title' => $title,
            'subjects' => [ucfirst(fake()->word()), ucfirst(fake()->word())],
            'html' => "<!DOCTYPE html><html><head><title>{$title}</title></head><body><h1>{$title}</h1></body></html>",
            'position' => fake()->unique()->numberBetween(1, 999),
        ];
    }
}
