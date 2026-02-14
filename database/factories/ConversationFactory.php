<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(['direct', 'group']),
            'created_by' => User::factory(),
        ];
    }

    public function direct(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'direct',
            'name' => null,
        ]);
    }

    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'group',
        ]);
    }
}
