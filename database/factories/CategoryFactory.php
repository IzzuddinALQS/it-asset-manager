<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => CategoryType::Asset,
            'tag_prefix' => strtoupper(fake()->lexify('??')),
        ];
    }

    public function component(): static
    {
        return $this->state(fn () => ['type' => CategoryType::Component, 'tag_prefix' => null]);
    }

    public function accessory(): static
    {
        return $this->state(fn () => ['type' => CategoryType::Accessory, 'tag_prefix' => null]);
    }

    public function license(): static
    {
        return $this->state(fn () => ['type' => CategoryType::License, 'tag_prefix' => null]);
    }
}
