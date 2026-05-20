<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\User;
use App\Models\Album;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $fileType = $this->faker->randomElement(['image', 'video']);
        $extension = $fileType === 'image' ? 'jpg' : 'mp4';
        
        return [
            'user_id' => User::factory(),
            'album_id' => null,
            'file_path' => "media/{$fileType}s/" . $this->faker->uuid . '.' . $extension,
            'file_name' => $this->faker->word . '.' . $extension,
            'file_type' => $fileType,
            'mime_type' => $fileType === 'image' ? 'image/jpeg' : 'video/mp4',
            'file_size' => $this->faker->numberBetween(100000, 5000000),
            'thumbnail_path' => $fileType === 'video' ? "media/thumbnails/" . $this->faker->uuid . '.jpg' : null,
            'description' => $this->faker->optional()->sentence(),
            'privacy' => $this->faker->randomElement(['public', 'friends_only', 'private']),
            'width' => $fileType === 'image' ? $this->faker->numberBetween(800, 3000) : null,
            'height' => $fileType === 'image' ? $this->faker->numberBetween(600, 2000) : null,
            'duration' => $fileType === 'video' ? $this->faker->numberBetween(10, 600) : null,
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'media/images/' . $this->faker->uuid . '.jpg',
            'file_name' => $this->faker->word . '.jpg',
            'file_type' => 'image',
            'mime_type' => 'image/jpeg',
            'thumbnail_path' => null,
            'width' => $this->faker->numberBetween(800, 3000),
            'height' => $this->faker->numberBetween(600, 2000),
            'duration' => null,
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'file_path' => 'media/videos/' . $this->faker->uuid . '.mp4',
            'file_name' => $this->faker->word . '.mp4',
            'file_type' => 'video',
            'mime_type' => 'video/mp4',
            'thumbnail_path' => 'media/thumbnails/' . $this->faker->uuid . '.jpg',
            'width' => null,
            'height' => null,
            'duration' => $this->faker->numberBetween(10, 600),
        ]);
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
