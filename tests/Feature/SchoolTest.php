<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 4 — School & Teacher.
 * CRUD sekolah: kelola hanya admin, lihat semua user login.
 */
class SchoolTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): string
    {
        return User::factory()->create(['role' => 'admin'])
            ->createToken('auth-token')->plainTextToken;
    }

    private function teacherToken(): string
    {
        return User::factory()->create(['role' => 'teacher'])
            ->createToken('auth-token')->plainTextToken;
    }

    public function test_admin_can_create_school(): void
    {
        $this->withToken($this->adminToken())
            ->postJson('/api/schools', [
                'name' => 'SMK N 2 Bandung',
                'npsn' => '20202020',
                'city' => 'Bandung',
                'phone' => '022123456',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'SMK N 2 Bandung')
            ->assertJsonPath('data.city', 'Bandung');

        $this->assertDatabaseHas('schools', ['name' => 'SMK N 2 Bandung']);
    }

    public function test_admin_can_update_and_delete_school(): void
    {
        $school = School::create(['name' => 'SMK Lama']);
        $token = $this->adminToken();

        $this->withToken($token)
            ->putJson("/api/schools/{$school->id}", ['name' => 'SMK Baru', 'city' => 'Bogor'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'SMK Baru');

        $this->assertDatabaseHas('schools', ['id' => $school->id, 'name' => 'SMK Baru']);

        $this->withToken($token)
            ->deleteJson("/api/schools/{$school->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('schools', ['id' => $school->id]);
    }

    public function test_non_admin_cannot_create_school(): void
    {
        $this->withToken($this->teacherToken())
            ->postJson('/api/schools', ['name' => 'Sekolah Ilegal'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('schools', ['name' => 'Sekolah Ilegal']);
    }

    public function test_non_admin_cannot_update_school(): void
    {
        $school = School::create(['name' => 'SMK N 1']);

        $this->withToken($this->teacherToken())
            ->putJson("/api/schools/{$school->id}", ['name' => 'Diubah'])
            ->assertStatus(403);
    }

    public function test_authenticated_user_can_list_and_view_school(): void
    {
        School::create(['name' => 'SMK N 1 Bandung', 'city' => 'Bandung']);
        School::create(['name' => 'SMK N 2 Bogor', 'city' => 'Bogor']);
        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/schools')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.meta.total', 2);

        $school = School::where('name', 'SMK N 1 Bandung')->firstOrFail();

        $this->withToken($token)
            ->getJson("/api/schools/{$school->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'SMK N 1 Bandung');
    }

    public function test_school_list_can_be_searched(): void
    {
        School::create(['name' => 'SMK N 1 Bandung', 'city' => 'Bandung']);
        School::create(['name' => 'SMA Harapan', 'city' => 'Bogor']);

        $this->withToken($this->teacherToken())
            ->getJson('/api/schools?q=Bandung')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.name', 'SMK N 1 Bandung');
    }

    public function test_school_endpoints_require_authentication(): void
    {
        $this->getJson('/api/schools')->assertStatus(401);
        $this->postJson('/api/schools', ['name' => 'X'])->assertStatus(401);
    }
}
