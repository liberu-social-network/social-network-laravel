<?php

namespace Database\Factories;

use App\Models\Share;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShareFactory extends Factory
{
    protected $model = Share::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
        ];
    }
}
