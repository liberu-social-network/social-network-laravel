<?php

namespace Database\Factories;

use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageAttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'message_id' => Message::factory(),
            'filename' => $this->faker->uuid . '.jpg',
            'original_filename' => $this->faker->word . '.jpg',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(1000, 5000000),
            'path' => 'message-attachments/' . $this->faker->uuid . '.jpg',
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'image/jpeg',
            'original_filename' => $this->faker->word . '.jpg',
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'application/pdf',
            'original_filename' => $this->faker->word . '.pdf',
        ]);
    }
}
