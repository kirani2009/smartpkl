<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — Student Profile.
 * Profil siswa (create, view, update).
 */
class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    private function studentToken(): string
    {
        return User::factory()->create(['role' => 'student'])
            ->createToken('auth-token')->plainTextToken;
    }

    public function test_student_can_create_own_profile(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student', [
                'school_id' => $school->id,
                'nis' => '12345',
                'class' => 'XII RPL',
                'entry_year' => 2024,
                'gender' => 'male',
                'birth_date' => '2006-05-15',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 10',
                'interests' => 'Web Development, Mobile Development',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.school.name', 'SMK N 1')
            ->assertJsonPath('data.nis', '12345')
            ->assertJsonPath('data.gender', 'male');

        $this->assertDatabaseHas('students', ['nis' => '12345']);
    }

    public function test_student_can_view_own_profile(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $user->student()->create([
            'school_id' => $school->id,
            'nis' => '12345',
            'class' => 'XII RPL',
        ]);

        $this->withToken($token)
            ->getJson('/api/me/student')
            ->assertStatus(200)
            ->assertJsonPath('data.school.name', 'SMK N 1')
            ->assertJsonPath('data.nis', '12345');
    }

    public function test_student_can_update_own_profile(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $user->student()->create([
            'school_id' => $school->id,
            'nis' => '12345',
        ]);

        $this->withToken($token)
            ->putJson('/api/me/student', [
                'nis' => '99999',
                'class' => 'XII TKJ',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.nis', '99999')
            ->assertJsonPath('data.class', 'XII TKJ');
    }

    public function test_duplicate_profile_creation_returns_409(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $user->student()->create(['school_id' => $school->id]);

        $this->withToken($token)
            ->postJson('/api/me/student', ['school_id' => $school->id])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_profile_creation_requires_school(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('school_id');
    }

    public function test_update_without_profile_returns_404(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->putJson('/api/me/student', ['nis' => 'X'])
            ->assertStatus(404);
    }

    public function test_non_student_cannot_access_student_profile(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $token = $teacher->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me/student')
            ->assertStatus(403);

        $this->withToken($token)
            ->postJson('/api/me/student', ['school_id' => 1])
            ->assertStatus(403);
    }

    public function test_student_profile_endpoints_require_authentication(): void
    {
        $this->getJson('/api/me/student')->assertStatus(401);
        $this->postJson('/api/me/student', ['school_id' => 1])->assertStatus(401);
    }
}
