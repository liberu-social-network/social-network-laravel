<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        $startTime = $this->faker->dateTimeBetween('now', '+6 months');
        $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(1, 8) . ' hours');

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraphs(3, true),
            'location' => $this->faker->address(),
            'image_url' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_attendees' => null,
            'is_public' => true,
        ];
    }

    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_url' => 'events/images/' . $this->faker->uuid . '.jpg',
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    public function withMaxAttendees(int $max = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'max_attendees' => $max,
        ]);
    }

    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = $this->faker->dateTimeBetween('-6 months', '-1 day');
            $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(1, 8) . ' hours');

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }

    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = $this->faker->dateTimeBetween('+1 day', '+6 months');
            $endTime = (clone $startTime)->modify('+' . $this->faker->numberBetween(1, 8) . ' hours');

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }
}
