<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'image_url' => null,
            'video_url' => null,
            'media_type' => 'text',
            'privacy' => 'public',
            'is_published' => true,
            'scheduled_at' => null,
        ];
    }

    public function scheduled($minutes = 60): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->addMinutes($minutes),
            'is_published' => false,
        ]);
    }

    public function scheduledForPublishing(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->subMinutes(5),
            'is_published' => false,
        ]);
    }

    public function withImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_url' => 'posts/images/' . $this->faker->uuid . '.jpg',
            'media_type' => 'image',
        ]);
    }

    public function withVideo(): static
    {
        return $this->state(fn (array $attributes) => [
            'video_url' => 'posts/videos/' . $this->faker->uuid . '.mp4',
            'media_type' => 'video',
        ]);
    }

    public function withMixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'image_url' => 'posts/images/' . $this->faker->uuid . '.jpg',
            'video_url' => 'posts/videos/' . $this->faker->uuid . '.mp4',
            'media_type' => 'mixed',
        ]);
    }
}
