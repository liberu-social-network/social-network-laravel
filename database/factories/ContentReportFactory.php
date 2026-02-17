<?php

namespace Database\Factories;

use App\Models\ContentReport;
use App\Models\User;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentReportFactory extends Factory
{
    protected $model = ContentReport::class;

    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
            'reason' => $this->faker->randomElement(['Spam', 'Harassment', 'Inappropriate Content', 'Hate Speech', 'Violence']),
            'description' => $this->faker->optional()->sentence(),
            'status' => 'pending',
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'admin_notes' => $this->faker->sentence(),
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dismissed',
            'reviewed_by' => User::factory(),
            'reviewed_at' => now(),
            'admin_notes' => $this->faker->sentence(),
        ]);
    }

    public function reviewing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reviewing',
        ]);
    }
}
