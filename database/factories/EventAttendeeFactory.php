<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventAttendeeFactory extends Factory
{
    protected $model = EventAttendee::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'status' => 'going',
        ];
    }

    public function going(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'going',
        ]);
    }

    public function maybe(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maybe',
        ]);
    }

    public function notGoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'not_going',
        ]);
    }
}
