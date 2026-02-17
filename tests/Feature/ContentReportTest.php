<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\ContentReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_a_post(): void
    {
        $reporter = User::factory()->create();
        $postOwner = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postOwner->id]);

        $response = $this->actingAs($reporter)->postJson('/api/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'Spam',
            'description' => 'This post contains spam content.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('content_reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);
    }

    public function test_user_can_report_a_comment(): void
    {
        $reporter = User::factory()->create();
        $commentOwner = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $commentOwner->id,
            'post_id' => $post->id,
        ]);

        $response = $this->actingAs($reporter)->postJson('/api/reports', [
            'reportable_type' => 'comment',
            'reportable_id' => $comment->id,
            'reason' => 'Harassment',
            'description' => 'This comment is harassing another user.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('content_reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
            'reason' => 'Harassment',
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_report_same_content_twice(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create();

        // First report
        ContentReport::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        // Attempt second report
        $response = $this->actingAs($reporter)->postJson('/api/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'Inappropriate',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'You have already reported this content.',
        ]);
    }

    public function test_user_can_view_their_reports(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create();
        
        ContentReport::factory()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $response = $this->actingAs($reporter)->getJson('/api/reports');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'reporter_id', 'reportable_type', 'reason', 'status'],
            ],
        ]);
    }

    public function test_user_can_cancel_pending_report(): void
    {
        $reporter = User::factory()->create();
        $post = Post::factory()->create();
        
        $report = ContentReport::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($reporter)->deleteJson("/api/reports/{$report->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('content_reports', [
            'id' => $report->id,
        ]);
    }

    public function test_user_cannot_cancel_reviewed_report(): void
    {
        $reporter = User::factory()->create();
        $admin = User::factory()->create();
        $post = Post::factory()->create();
        
        $report = ContentReport::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'Spam',
            'status' => 'resolved',
            'reviewed_by' => $admin->id,
        ]);

        $response = $this->actingAs($reporter)->deleteJson("/api/reports/{$report->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Cannot cancel a report that has been reviewed.',
        ]);
    }

    public function test_user_cannot_cancel_others_report(): void
    {
        $reporter = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create();
        
        $report = ContentReport::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'Spam',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($otherUser)->deleteJson("/api/reports/{$report->id}");

        $response->assertStatus(403);
    }

    public function test_post_starts_with_approved_moderation_status(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertEquals('approved', $post->moderation_status);
    }

    public function test_comment_starts_with_approved_moderation_status(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        $this->assertEquals('approved', $comment->moderation_status);
    }

    public function test_can_scope_approved_posts(): void
    {
        Post::factory()->create(['moderation_status' => 'approved']);
        Post::factory()->create(['moderation_status' => 'rejected']);
        Post::factory()->create(['moderation_status' => 'pending']);

        $approvedPosts = Post::approved()->get();

        $this->assertCount(1, $approvedPosts);
        $this->assertEquals('approved', $approvedPosts->first()->moderation_status);
    }

    public function test_can_scope_approved_comments(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id, 'moderation_status' => 'approved']);
        Comment::factory()->create(['post_id' => $post->id, 'moderation_status' => 'rejected']);
        Comment::factory()->create(['post_id' => $post->id, 'moderation_status' => 'pending']);

        $approvedComments = Comment::approved()->get();

        $this->assertCount(1, $approvedComments);
        $this->assertEquals('approved', $approvedComments->first()->moderation_status);
    }
}
