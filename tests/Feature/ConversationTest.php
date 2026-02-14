<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }

    public function test_user_can_create_direct_conversation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);

        $response = $this->postJson('/api/conversations', [
            'type' => 'direct',
            'participant_ids' => [$user2->id],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'type',
            'created_by',
            'participants',
        ]);

        $this->assertDatabaseHas('conversations', [
            'type' => 'direct',
            'created_by' => $user1->id,
        ]);
    }

    public function test_creating_duplicate_direct_conversation_returns_existing()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Sanctum::actingAs($user1);

        // Create first conversation
        $response1 = $this->postJson('/api/conversations', [
            'type' => 'direct',
            'participant_ids' => [$user2->id],
        ]);

        $conversation1Id = $response1->json('id');

        // Try to create duplicate
        $response2 = $this->postJson('/api/conversations', [
            'type' => 'direct',
            'participant_ids' => [$user2->id],
        ]);

        $response2->assertStatus(200);
        $this->assertEquals($conversation1Id, $response2->json('id'));
    }

    public function test_user_can_create_group_conversation()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Sanctum::actingAs($user1);

        $response = $this->postJson('/api/conversations', [
            'type' => 'group',
            'name' => 'Team Chat',
            'participant_ids' => [$user2->id, $user3->id],
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'type' => 'group',
            'name' => 'Team Chat',
        ]);

        $conversation = Conversation::find($response->json('id'));
        $this->assertCount(3, $conversation->participants); // Creator + 2 participants
    }

    public function test_user_can_list_their_conversations()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create a conversation
        $conversation = Conversation::factory()->direct()->create(['created_by' => $user->id]);
        $conversation->addParticipant($user);
        $conversation->addParticipant($otherUser);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/conversations');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'type', 'participants']
            ]
        ]);
    }

    public function test_user_can_view_conversation_details()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->direct()->create();
        $conversation->addParticipant($user);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $conversation->id,
        ]);
    }

    public function test_user_cannot_view_conversation_they_are_not_part_of()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $conversation = Conversation::factory()->direct()->create();
        $conversation->addParticipant($otherUser);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/conversations/{$conversation->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_add_participants_to_group_conversation()
    {
        $user = User::factory()->create();
        $newParticipant = User::factory()->create();
        
        $conversation = Conversation::factory()->group()->create(['created_by' => $user->id]);
        $conversation->addParticipant($user);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/conversations/{$conversation->id}/participants", [
            'user_ids' => [$newParticipant->id],
        ]);

        $response->assertStatus(200);
        
        $this->assertTrue($conversation->fresh()->participants()->where('user_id', $newParticipant->id)->exists());
    }

    public function test_user_cannot_add_participants_to_direct_conversation()
    {
        $user = User::factory()->create();
        $newParticipant = User::factory()->create();
        
        $conversation = Conversation::factory()->direct()->create();
        $conversation->addParticipant($user);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/conversations/{$conversation->id}/participants", [
            'user_ids' => [$newParticipant->id],
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_remove_themselves_from_conversation()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->group()->create();
        $conversation->addParticipant($user);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/conversations/{$conversation->id}/participants/{$user->id}");

        $response->assertStatus(200);
        
        $participant = $conversation->fresh()->participants()->where('user_id', $user->id)->first();
        $this->assertNotNull($participant->pivot->left_at);
    }

    public function test_creator_can_remove_other_participants()
    {
        $creator = User::factory()->create();
        $participant = User::factory()->create();
        
        $conversation = Conversation::factory()->group()->create(['created_by' => $creator->id]);
        $conversation->addParticipant($creator);
        $conversation->addParticipant($participant);

        Sanctum::actingAs($creator);

        $response = $this->deleteJson("/api/conversations/{$conversation->id}/participants/{$participant->id}");

        $response->assertStatus(200);
    }

    public function test_non_creator_cannot_remove_other_participants()
    {
        $creator = User::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        
        $conversation = Conversation::factory()->group()->create(['created_by' => $creator->id]);
        $conversation->addParticipant($user);
        $conversation->addParticipant($otherUser);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/conversations/{$conversation->id}/participants/{$otherUser->id}");

        $response->assertStatus(403);
    }
}
