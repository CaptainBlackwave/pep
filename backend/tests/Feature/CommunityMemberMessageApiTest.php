<?php

namespace Tests\Feature;

use App\Core\Models\CommunityDiscussion;
use App\Core\Models\CommunityMessageThread;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityMemberMessageApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sanctum::actingAs(User::factory()->create());
    }

    public function test_private_member_index_and_detail_return_users(): void
    {
        $user = User::factory()->create([
            'name' => 'LabReport',
            'username' => 'LabReport',
            'bio' => 'Lab results expert',
            'last_active' => now(),
        ]);

        CommunityDiscussion::create([
            'user_id' => $user->id,
            'title' => 'Posted a lab result',
            'slug' => 'posted-a-lab-result',
            'body' => 'Dynamic user activity.',
            'status' => 'published',
            'last_reply_at' => now(),
        ]);

        $this->getJson('/api/v1/community/members?search=LabReport')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'LabReport')
            ->assertJsonPath('data.0.is_online', true);

        $this->getJson('/api/v1/community/members/LabReport')
            ->assertOk()
            ->assertJsonPath('data.username', 'LabReport')
            ->assertJsonPath('data.stats.discussions', 1)
            ->assertJsonPath('data.activities.0.title', 'Started Posted a lab result');
    }

    public function test_message_index_detail_and_authenticated_reply_are_dynamic(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create([
            'name' => 'LabReport',
            'username' => 'LabReport',
        ]);

        Sanctum::actingAs($owner);

        $thread = CommunityMessageThread::create([
            'user_id' => $owner->id,
            'participant_user_id' => $participant->id,
            'last_message_at' => now(),
            'status' => 'active',
        ]);
        $thread->messages()->create([
            'sender_user_id' => $participant->id,
            'body' => 'Do you have the COA available?',
            'sent_at' => now(),
        ]);

        $this->getJson('/api/v1/community/messages')
            ->assertOk()
            ->assertJsonPath('data.0.participant.slug', 'LabReport');

        $this->getJson("/api/v1/community/messages/{$thread->id}")
            ->assertOk()
            ->assertJsonPath('data.messages.0.body', 'Do you have the COA available?')
            ->assertJsonPath('data.messages.0.side', 'in');

        $this->postJson("/api/v1/community/messages/{$thread->id}/messages", [
            'body' => 'Here is the report.',
        ])->assertCreated()
            ->assertJsonPath('data.side', 'out');

        $this->assertDatabaseHas('community_messages', [
            'thread_id' => $thread->id,
            'body' => 'Here is the report.',
        ]);
    }

    public function test_authenticated_user_can_start_message_thread_with_member(): void
    {
        $owner = User::factory()->create();
        $participant = User::factory()->create(['username' => 'ResearcherX']);

        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/community/messages', [
            'participant_username' => 'ResearcherX',
            'body' => 'Can I ask you about your report?',
        ])->assertCreated()
            ->assertJsonPath('data.participant.slug', 'ResearcherX')
            ->assertJsonPath('data.messages.0.side', 'out');

        $this->assertDatabaseHas('community_message_threads', [
            'user_id' => $owner->id,
            'participant_user_id' => $participant->id,
        ]);
    }

    public function test_message_thread_shows_the_other_member_for_either_participant(): void
    {
        $owner = User::factory()->create(['username' => 'OwnerUser']);
        $participant = User::factory()->create(['username' => 'ParticipantUser']);

        $thread = CommunityMessageThread::create([
            'user_id' => $owner->id,
            'participant_user_id' => $participant->id,
            'owner_unread_count' => 2,
            'participant_unread_count' => 3,
            'unread_count' => 5,
            'last_message_at' => now(),
            'status' => 'active',
        ]);

        Sanctum::actingAs($participant);

        $this->getJson("/api/v1/community/messages/{$thread->id}")
            ->assertOk()
            ->assertJsonPath('data.participant.slug', 'OwnerUser')
            ->assertJsonPath('data.unread_count', 0);

        $this->assertDatabaseHas('community_message_threads', [
            'id' => $thread->id,
            'owner_unread_count' => 2,
            'participant_unread_count' => 0,
            'unread_count' => 2,
        ]);
    }

    public function test_authenticated_user_cannot_start_message_thread_with_self(): void
    {
        $user = User::factory()->create(['username' => 'SelfUser']);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/community/messages', [
            'participant_user_id' => $user->id,
        ])->assertStatus(422);
    }

    public function test_self_message_threads_are_not_returned(): void
    {
        $user = User::factory()->create(['username' => 'SelfUser']);

        CommunityMessageThread::create([
            'user_id' => $user->id,
            'participant_user_id' => $user->id,
            'last_message_at' => now(),
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/community/messages')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
