<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;

class PublishScheduledPosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled posts whose scheduled_at time has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $posts = Post::scheduledForPublishing()->get();

        if ($posts->isEmpty()) {
            $this->info('No posts to publish.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($posts as $post) {
            $post->update(['is_published' => true]);
            $count++;
            $this->info("Published post ID: {$post->id} - \"{$post->content}\"");
        }

        $this->info("Successfully published {$count} post(s).");
        
        return self::SUCCESS;
    }
}
