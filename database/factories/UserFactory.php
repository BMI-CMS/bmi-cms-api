<?php

namespace Database\Factories;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'name' => fake()->name(),
            'user_level_id' => fn () => UserLevel::first()?->level ?? UserLevel::factory()->create()->level,
            'phone_imei' => fake()->numerify('###############'),
            'remember_token' => Str::random(10),
        ];
    }
}
