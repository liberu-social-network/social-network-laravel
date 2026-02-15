<?php

namespace Database\Factories;

use App\Models\Album;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlbumFactory extends Factory
{
    protected $model = Album::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->paragraph(),
            'cover_image' => null,
            'privacy' => $this->faker->randomElement(['public', 'friends_only', 'private']),
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => 'public',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => 'private',
        ]);
    }

    public function friendsOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => 'friends_only',
        ]);
    }
}
