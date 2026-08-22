<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 13 — Notification.
 * Notifikasi user: daftar, baca, tandai sudah dibaca, hapus.
 */
class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'student']);
    }

    private function userToken(): string
    {
        return $this->user->createToken('auth-token')->plainTextToken;
    }

    private function createNotification(array $overrides = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $this->user->id,
            'type' => 'APPLICATION_STATUS_CHANGED',
            'title' => 'Status Lamaran Diperbarui',
            'message' => 'Lamaran Anda diterima.',
        ], $overrides));
    }

    // ---- List ----

    public function test_user_can_list_notifications(): void
    {
        $this->createNotification(['title' => 'Notif 1']);
        $this->createNotification(['title' => 'Notif 2']);

        // Other user's notification
        $other = User::factory()->create();
        Notification::create([
            'user_id' => $other->id,
            'type' => 'TEST',
            'title' => 'Other Notif',
            'message' => 'Other',
        ]);

        $token = $this->userToken();

        $this->withToken($token)
            ->getJson('/api/notifications')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_user_can_filter_by_type(): void
    {
        $this->createNotification(['type' => 'TYPE_A']);
        $this->createNotification(['type' => 'TYPE_B']);

        $token = $this->userToken();

        $this->withToken($token)
            ->getJson('/api/notifications?type=TYPE_A')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.type', 'TYPE_A');
    }

    public function test_user_can_filter_unread_only(): void
    {
        $this->createNotification(['read_at' => null]);
        $this->createNotification(['read_at' => now()]);

        $token = $this->userToken();

        $this->withToken($token)
            ->getJson('/api/notifications?unread_only=1')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.is_read', false);
    }

    // ---- Unread Count ----

    public function test_user_can_get_unread_count(): void
    {
        $this->createNotification(['read_at' => null]);
        $this->createNotification(['read_at' => null]);
        $this->createNotification(['read_at' => now()]);

        $token = $this->userToken();

        $this->withToken($token)
            ->getJson('/api/notifications/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('data.count', 2);
    }

    // ---- Show ----

    public function test_user_can_view_notification_detail(): void
    {
        $notification = $this->createNotification();

        $token = $this->userToken();

        $this->withToken($token)
            ->getJson("/api/notifications/{$notification->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Status Lamaran Diperbarui');
    }

    public function test_user_cannot_view_other_users_notification(): void
    {
        $other = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $other->id,
            'type' => 'TEST',
            'title' => 'Other',
            'message' => 'Other',
        ]);

        $token = $this->userToken();

        $this->withToken($token)
            ->getJson("/api/notifications/{$notification->id}")
            ->assertStatus(403);
    }

    // ---- Mark as Read ----

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = $this->createNotification(['read_at' => null]);

        $token = $this->userToken();

        $this->withToken($token)
            ->putJson("/api/notifications/{$notification->id}/read")
            ->assertStatus(200)
            ->assertJsonPath('data.is_read', true);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_already_read_notification_stays_read(): void
    {
        $readTime = now()->subHour();
        $notification = $this->createNotification(['read_at' => $readTime]);

        $token = $this->userToken();

        $this->withToken($token)
            ->putJson("/api/notifications/{$notification->id}/read")
            ->assertStatus(200);

        $notification->refresh();
        $this->assertEquals($readTime->timestamp, $notification->read_at->timestamp);
    }

    // ---- Mark All as Read ----

    public function test_user_can_mark_all_as_read(): void
    {
        $this->createNotification(['read_at' => null]);
        $this->createNotification(['read_at' => null]);
        $this->createNotification(['read_at' => now()]);

        $token = $this->userToken();

        $this->withToken($token)
            ->putJson('/api/notifications/read-all')
            ->assertStatus(200)
            ->assertJsonPath('data.updated', 2);

        $remaining = \App\Models\Notification::where('user_id', $this->user->id)->whereNull('read_at')->count();
        $this->assertEquals(0, $remaining);
    }

    // ---- Delete ----

    public function test_user_can_delete_notification(): void
    {
        $notification = $this->createNotification();

        $token = $this->userToken();

        $this->withToken($token)
            ->deleteJson("/api/notifications/{$notification->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('user_notifications', ['id' => $notification->id]);
    }

    public function test_user_cannot_delete_other_users_notification(): void
    {
        $other = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $other->id,
            'type' => 'TEST',
            'title' => 'Other',
            'message' => 'Other',
        ]);

        $token = $this->userToken();

        $this->withToken($token)
            ->deleteJson("/api/notifications/{$notification->id}")
            ->assertStatus(403);
    }

    // ---- Auth ----

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
        $this->getJson('/api/notifications/unread-count')->assertStatus(401);
        $this->putJson('/api/notifications/read-all')->assertStatus(401);
    }
}
