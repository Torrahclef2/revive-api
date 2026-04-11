<?php

namespace Tests\Feature;

use App\Events\SessionEnded;
use App\Events\SessionWentLive;
use App\Models\PrayerSession;
use App\Models\SessionMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    // ===== Discovery Tests =====

    public function test_discovery_returns_paginated_discoverable_sessions()
    {
        $user = User::factory()
            ->create([
                'location_country' => 'US',
                'gender' => 'male',
            ]);

        PrayerSession::factory()
            ->count(3)
            ->create([
                'visibility' => 'open',
                'status' => 'upcoming',
                'location_country' => 'US',
                'gender_preference' => 'any',
            ]);

        $response = $this->actingAs($user)->getJson('/api/v1/sessions/discovery');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['*' => ['id', 'title', 'description', 'visibility']],
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
        $this->assertEquals(3, $response->json('meta.total'));
    }

    public function test_discovery_filters_sessions_by_country_location()
    {
        $user = User::factory()->create(['location_country' => 'US', 'gender' => 'male']);

        PrayerSession::factory()->create(['visibility' => 'open', 'status' => 'upcoming', 'location_country' => 'US']);
        PrayerSession::factory()->create(['visibility' => 'open', 'status' => 'upcoming', 'location_country' => 'UK']);

        $response = $this->actingAs($user)->getJson('/api/v1/sessions/discovery?location_country=US');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_discovery_anonymizes_user_data_for_anonymous_sessions()
    {
        $user = User::factory()->create(['location_country' => 'US', 'gender' => 'male']);

        PrayerSession::factory()->create([
            'visibility' => 'anonymous',
            'status' => 'upcoming',
            'location_country' => 'US',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/sessions/discovery');

        $response->assertStatus(200);
        $sessionData = $response->json('data.0');
        $this->assertArrayNotHasKey('host', $sessionData);
        $this->assertEquals('anonymous', $sessionData['visibility']);
    }

    public function test_discovery_excludes_ended_sessions()
    {
        $user = User::factory()->create(['location_country' => 'US', 'gender' => 'male']);

        PrayerSession::factory()->create(['visibility' => 'open', 'status' => 'upcoming', 'location_country' => 'US']);
        PrayerSession::factory()->create(['visibility' => 'open', 'status' => 'ended', 'location_country' => 'US']);

        $response = $this->actingAs($user)->getJson('/api/v1/sessions/discovery');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_discovery_excludes_full_sessions()
    {
        $user = User::factory()->create(['location_country' => 'US', 'gender' => 'male']);

        $session = PrayerSession::factory()->create([
            'visibility' => 'open',
            'status' => 'upcoming',
            'location_country' => 'US',
            'max_members' => 2,
        ]);

        SessionMember::factory()->count(2)->create(['session_id' => $session->id, 'status' => 'admitted']);

        $response = $this->actingAs($user)->getJson('/api/v1/sessions/discovery');

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('meta.total'));
    }

    public function test_discovery_respects_gender_preferences()
    {
        $maleUser = User::factory()->create(['location_country' => 'US', 'gender' => 'male']);
        $femaleUser = User::factory()->create(['location_country' => 'US', 'gender' => 'female']);

        PrayerSession::factory()->create([
            'visibility' => 'open',
            'status' => 'upcoming',
            'location_country' => 'US',
            'gender_preference' => 'male',
        ]);

        $maleResponse = $this->actingAs($maleUser)->getJson('/api/v1/sessions/discovery');
        $femaleResponse = $this->actingAs($femaleUser)->getJson('/api/v1/sessions/discovery');

        $maleResponse->assertStatus(200);
        $this->assertEquals(1, $maleResponse->json('meta.total'));

        $femaleResponse->assertStatus(200);
        $this->assertEquals(0, $femaleResponse->json('meta.total'));
    }

    // ===== Store Tests =====

    public function test_store_creates_new_prayer_session_with_host_auto_admission()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/sessions', [
            'title' => 'Evening Prayer Circle',
            'description' => 'Join us for evening prayers',
            'purpose' => 'prayer',
            'template' => 'standard',
            'visibility' => 'open',
            'gender_preference' => 'any',
            'location_city' => 'New York',
            'location_country' => 'US',
            'max_members' => 10,
            'scheduled_at' => now()->addHours(1),
            'duration_minutes' => 30,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'title', 'host_id', 'status', 'agora_channel_name'],
        ]);

        $session = PrayerSession::where('title', 'Evening Prayer Circle')->first();
        $this->assertNotNull($session);
        $this->assertEquals($user->id, $session->host_id);
        $this->assertEquals('upcoming', $session->status);
        $this->assertNotNull($session->agora_channel_name);

        // Verify host is auto-admitted
        $member = SessionMember::where('session_id', $session->id)
            ->where('user_id', $user->id)
            ->first();
        $this->assertNotNull($member);
        $this->assertEquals('admitted', $member->status);
        $this->assertNotNull($member->joined_at);
    }

    public function test_store_requires_authentication()
    {
        $response = $this->postJson('/api/v1/sessions', [
            'title' => 'Evening Prayer Circle',
        ]);

        $response->assertStatus(401);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/sessions', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'visibility', 'scheduled_at', 'duration_minutes']);
    }

    public function test_store_validates_scheduled_at_is_in_future()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/sessions', [
            'title' => 'Evening Prayer Circle',
            'description' => 'Join us for evening prayers',
            'purpose' => 'prayer',
            'template' => 'standard',
            'visibility' => 'open',
            'gender_preference' => 'any',
            'location_city' => 'New York',
            'location_country' => 'US',
            'max_members' => 10,
            'scheduled_at' => now()->subHours(1),
            'duration_minutes' => 30,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['scheduled_at']);
    }

    public function test_store_generates_unique_agora_channel_names()
    {
        $user = User::factory()->create();

        $response1 = $this->actingAs($user)->postJson('/api/v1/sessions', [
            'title' => 'Session 1',
            'description' => 'Test',
            'purpose' => 'prayer',
            'template' => 'standard',
            'visibility' => 'open',
            'gender_preference' => 'any',
            'location_city' => 'New York',
            'location_country' => 'US',
            'max_members' => 10,
            'scheduled_at' => now()->addHours(1),
            'duration_minutes' => 30,
        ]);

        $response2 = $this->actingAs($user)->postJson('/api/v1/sessions', [
            'title' => 'Session 2',
            'description' => 'Test',
            'purpose' => 'prayer',
            'template' => 'standard',
            'visibility' => 'open',
            'gender_preference' => 'any',
            'location_city' => 'New York',
            'location_country' => 'US',
            'max_members' => 10,
            'scheduled_at' => now()->addHours(2),
            'duration_minutes' => 30,
        ]);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $channel1 = $response1->json('data.agora_channel_name');
        $channel2 = $response2->json('data.agora_channel_name');

        $this->assertNotEquals($channel1, $channel2);
    }

    // ===== Show Tests =====

    public function test_show_returns_session_details_with_host_information()
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'visibility' => 'open',
        ]);

        SessionMember::factory()->create(['session_id' => $session->id, 'user_id' => $host->id, 'status' => 'admitted']);

        $response = $this->actingAs($user)->getJson("/api/v1/sessions/{$session->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'title', 'host' => ['id', 'username']],
        ]);
        $this->assertEquals($host->id, $response->json('data.host.id'));
    }

    public function test_show_anonymizes_data_for_anonymous_sessions()
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'visibility' => 'anonymous',
        ]);

        SessionMember::factory()->create(['session_id' => $session->id, 'user_id' => $host->id, 'status' => 'admitted']);

        $response = $this->actingAs($user)->getJson("/api/v1/sessions/{$session->id}");

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('host', $response->json('data'));
        $this->assertEquals('anonymous', $response->json('data.visibility'));
    }

    public function test_show_allows_host_to_view_ended_session()
    {
        $host = User::factory()->create();

        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'ended',
            'visibility' => 'open',
        ]);

        $response = $this->actingAs($host)->getJson("/api/v1/sessions/{$session->id}");

        $response->assertStatus(200);
    }

    public function test_show_prevents_non_host_from_viewing_ended_session()
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'ended',
            'visibility' => 'open',
        ]);

        $response = $this->actingAs($user)->getJson("/api/v1/sessions/{$session->id}");

        $response->assertStatus(404);
    }

    public function test_show_requires_authentication()
    {
        $session = PrayerSession::factory()->create();

        $response = $this->getJson("/api/v1/sessions/{$session->id}");

        $response->assertStatus(401);
    }

    public function test_show_returns_404_for_non_existent_session()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/sessions/nonexistent');

        $response->assertStatus(404);
    }

    // ===== Go Live Tests =====

    public function test_go_live_host_can_start_upcoming_session()
    {
        $host = User::factory()->create();
        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($host)->postJson("/api/v1/sessions/{$session->id}/go-live");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'status', 'live_started_at'],
        ]);

        $this->assertEquals('live', $response->json('data.status'));
        $this->assertNotNull($response->json('data.live_started_at'));

        $session->refresh();
        $this->assertEquals('live', $session->status);
        $this->assertNotNull($session->live_started_at);
    }

    public function test_go_live_host_can_start_admitting_session()
    {
        $host = User::factory()->create();
        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'admitting',
        ]);

        $response = $this->actingAs($host)->postJson("/api/v1/sessions/{$session->id}/go-live");

        $response->assertStatus(200);
        $this->assertEquals('live', $response->json('data.status'));
    }

    public function test_go_live_prevents_non_host_from_starting_session()
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/sessions/{$session->id}/go-live");

        $response->assertStatus(403);
        $this->assertFalse($response->json('success'));

        $session->refresh();
        $this->assertEquals('upcoming', $session->status);
    }

    public function test_go_live_prevents_starting_live_session()
    {
        $host = User::factory()->create();
        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'live',
        ]);

        $response = $this->actingAs($host)->postJson("/api/v1/sessions/{$session->id}/go-live");

        $response->assertStatus(409);
        $this->assertFalse($response->json('success'));
    }

    public function test_go_live_prevents_starting_ended_session()
    {
        $host = User::factory()->create();
        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'ended',
        ]);

        $response = $this->actingAs($host)->postJson("/api/v1/sessions/{$session->id}/go-live");

        $response->assertStatus(409);
        $this->assertFalse($response->json('success'));
    }

    public function test_go_live_requires_authentication()
    {
        $session = PrayerSession::factory()->create();

        $response = $this->postJson("/api/v1/sessions/{$session->id}/go-live");

        $response->assertStatus(401);
    }

    // ===== End Tests =====

    public function test_end_host_can_end_live_session()
    {
        $host = User::factory()->create();
        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'live',
            'live_started_at' => now()->subHours(1),
        ]);

        $response = $this->actingAs($host)->postJson("/api/v1/sessions/{$session->id}/end");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => ['id', 'status', 'live_ended_at'],
        ]);

        $this->assertEquals('ended', $response->json('data.status'));
        $this->assertNotNull($response->json('data.live_ended_at'));

        $session->refresh();
        $this->assertEquals('ended', $session->status);
        $this->assertNotNull($session->live_ended_at);
    }

    public function test_end_prevents_non_host_from_ending_session()
    {
        $host = User::factory()->create();
        $user = User::factory()->create();

        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'live',
        ]);

        $response = $this->actingAs($user)->postJson("/api/v1/sessions/{$session->id}/end");

        $response->assertStatus(403);
        $this->assertFalse($response->json('success'));

        $session->refresh();
        $this->assertEquals('live', $session->status);
    }

    public function test_end_prevents_ending_non_live_session()
    {
        $host = User::factory()->create();
        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'upcoming',
        ]);

        $response = $this->actingAs($host)->postJson("/api/v1/sessions/{$session->id}/end");

        $response->assertStatus(409);
        $this->assertFalse($response->json('success'));
    }

    public function test_end_prevents_ending_already_ended_session()
    {
        $host = User::factory()->create();
        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'ended',
            'live_ended_at' => now(),
        ]);

        $response = $this->actingAs($host)->postJson("/api/v1/sessions/{$session->id}/end");

        $response->assertStatus(409);
        $this->assertFalse($response->json('success'));
    }

    public function test_end_requires_authentication()
    {
        $session = PrayerSession::factory()->create();

        $response = $this->postJson("/api/v1/sessions/{$session->id}/end");

        $response->assertStatus(401);
    }

    public function test_end_fires_session_ended_event()
    {
        \Illuminate\Support\Facades\Event::fake();

        $host = User::factory()->create();
        $session = PrayerSession::factory()->create([
            'host_id' => $host->id,
            'status' => 'live',
        ]);

        $this->actingAs($host)->postJson("/api/v1/sessions/{$session->id}/end");

        \Illuminate\Support\Facades\Event::assertDispatched(SessionEnded::class, function ($event) use ($session) {
            return $event->session->id === $session->id;
        });
    }
}

