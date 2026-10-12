<?php

namespace Database\Factories;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLevel>
 */
class UserLevelFactory extends Factory
{
    protected $model = UserLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'level' => fake()->unique()->numberBetween(100, 999999),
            'name' => substr(fake()->unique()->jobTitle(), 0, 50),
        ];
    }
}
