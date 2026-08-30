<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 4 — School & Teacher.
 * Profil guru (profil sendiri, role teacher).
 */
class TeacherProfileTest extends TestCase
{
    use RefreshDatabase;

    private function teacherToken(): string
    {
        return User::factory()->create(['role' => 'teacher'])
            ->createToken('auth-token')->plainTextToken;
    }

    public function test_teacher_can_create_own_profile(): void
    {
        $school = School::create(['name' => 'SMK N 1 Bandung']);
        $user = User::factory()->create(['role' => 'teacher', 'name' => 'Pak Budi']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/me/teacher', [
                'teacher_name' => 'Pak Budi',
                'school_name' => 'SMK N 1 Bandung',
                'school_id' => $school->id,
                'nip' => '198001012010011001',
                'position' => 'Guru Pembimbing PKL',
                'phone' => '081234567890',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.school.id', $school->id)
            ->assertJsonPath('data.position', 'Guru Pembimbing PKL')
            ->assertJsonPath('data.user.name', 'Pak Budi');

        $this->assertDatabaseHas('teachers', ['user_id' => $user->id, 'school_id' => $school->id]);
    }

    public function test_teacher_can_view_and_update_own_profile(): void
    {
        $school = School::create(['name' => 'SMK N 1 Bandung']);
        $user = User::factory()->create(['role' => 'teacher']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $user->teacher()->create(['school_id' => $school->id, 'position' => 'Guru Produktif']);

        $this->withToken($token)
            ->getJson('/api/me/teacher')
            ->assertStatus(200)
            ->assertJsonPath('data.school.name', 'SMK N 1 Bandung');

        $this->withToken($token)
            ->putJson('/api/me/teacher', [
                'position' => 'Kepala Program RPL',
                'phone' => '081111111',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.position', 'Kepala Program RPL')
            ->assertJsonPath('data.phone', '081111111');
    }

    public function test_duplicate_profile_creation_returns_409(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $user = User::factory()->create(['role' => 'teacher']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $user->teacher()->create(['school_id' => $school->id]);

        $this->withToken($token)
            ->postJson('/api/me/teacher', [
                'teacher_name' => 'Guru Duplikat',
                'school_name' => 'SMK N 1',
                'school_id' => $school->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_profile_creation_requires_valid_school(): void
    {
        $token = $this->teacherToken();

        $this->withToken($token)
            ->postJson('/api/me/teacher', ['school_id' => 9999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('school_id');
    }

    public function test_update_without_profile_returns_404(): void
    {
        $token = $this->teacherToken();

        $this->withToken($token)
            ->putJson('/api/me/teacher', ['position' => 'X'])
            ->assertStatus(404);
    }

    public function test_non_teacher_cannot_access_teacher_profile(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/me/teacher', ['school_id' => 1])
            ->assertStatus(403);

        $this->withToken($token)
            ->getJson('/api/me/teacher')
            ->assertStatus(403);
    }

    public function test_teacher_profile_endpoints_require_authentication(): void
    {
        $this->getJson('/api/me/teacher')->assertStatus(401);
        $this->postJson('/api/me/teacher', ['school_id' => 1])->assertStatus(401);
    }
}
