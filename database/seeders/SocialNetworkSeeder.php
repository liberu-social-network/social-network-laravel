<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Share;
use Illuminate\Database\Seeder;

class SocialNetworkSeeder extends Seeder
{
    public function run(): void
    {
        // Create users
        $users = User::factory(10)->create();

        // Create posts for each user
        $users->each(function ($user) {
            // Create text posts
            Post::factory(3)->create(['user_id' => $user->id]);
            
            // Create posts with images
            Post::factory(2)->withImage()->create(['user_id' => $user->id]);
            
            // Create posts with videos
            Post::factory(1)->withVideo()->create(['user_id' => $user->id]);
        });

        // Get all posts
        $posts = Post::all();

        // Create comments, likes, and shares
        $posts->each(function ($post) use ($users) {
            // Random number of comments (0-5)
            Comment::factory(rand(0, 5))->create([
                'post_id' => $post->id,
                'user_id' => $users->random()->id,
            ]);

            // Random number of likes (0-8)
            $likeCount = rand(0, 8);
            for ($i = 0; $i < $likeCount; $i++) {
                try {
                    Like::create([
                        'post_id' => $post->id,
                        'user_id' => $users->random()->id,
                    ]);
                } catch (\Exception $e) {
                    // Skip if duplicate (user already liked this post)
                }
            }

            // Random number of shares (0-3)
            $shareCount = rand(0, 3);
            for ($i = 0; $i < $shareCount; $i++) {
                try {
                    Share::create([
                        'post_id' => $post->id,
                        'user_id' => $users->random()->id,
                    ]);
                } catch (\Exception $e) {
                    // Skip if duplicate (user already shared this post)
                }
            }
        });
    }
}
