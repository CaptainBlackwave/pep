<?php

namespace Tests\Feature;

use App\Core\Models\GuestSession;
use App\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineActivityApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_heartbeat_tracks_viewing_activity(): void
    {
        $this->postJson('/api/v1/ws/heartbeat', [
            'guest_id' => 'guest-test-1',
            'path' => '/discussions/helpful-thread?from=sidebar',
            'label' => 'Discussion thread',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('guests', 1)
            ->assertJsonPath('guest_activity.0.label', 'Discussion thread')
            ->assertJsonPath('guest_activity.0.path', '/discussions/helpful-thread')
            ->assertJsonPath('guest_activity.0.visitors', 1);

        $this->assertDatabaseHas('guest_sessions', [
            'guest_id' => 'guest-test-1',
            'current_path' => '/discussions/helpful-thread',
            'current_label' => 'Discussion thread',
        ]);
    }

    public function test_online_count_includes_members_guests_visits_and_activity(): void
    {
        User::factory()->create(['last_active' => now()]);
        GuestSession::create([
            'guest_id' => 'guest-test-2',
            'current_path' => '/members',
            'current_label' => 'Members',
            'last_active' => now(),
        ]);

        $this->getJson('/api/v1/ws/online-count')
            ->assertOk()
            ->assertJsonPath('members', 1)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('guests', 1)
            ->assertJsonPath('visits_today', 2)
            ->assertJsonPath('guest_activity.0.label', 'Members')
            ->assertJsonPath('guest_activity.0.visitors', 1);
    }
}
