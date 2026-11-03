<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Category;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
final class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'FBC '.fake()->unique()->city(),
            'category' => Category::Men,
            // Like a team added in the app, the slug is made of the name and the category's label (ADR 0003).
            'slug' => function (array $attributes): string {
                $category = $attributes['category'] instanceof Category ? $attributes['category'] : Category::from($attributes['category']);

                return Str::slug("{$attributes['name']} {$category->label()}");
            },
        ];
    }
}
