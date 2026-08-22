<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — Student Profile.
 * Profil siswa (create, view, update) + security tests.
 */
class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    private function studentToken(): string
    {
        return User::factory()->create(['role' => 'student'])
            ->createToken('auth-token')->plainTextToken;
    }

    // ---- CRUD Tests ----

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

    public function test_profile_returns_completeness(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $user->student()->create([
            'school_id' => $school->id,
            'nis' => '12345',
            'class' => 'XII RPL',
        ]);

        $response = $this->withToken($token)
            ->getJson('/api/me/student')
            ->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('profile_completeness', $data);
        $this->assertArrayHasKey('missing_fields', $data);
        $this->assertIsInt($data['profile_completeness']);
        $this->assertIsArray($data['missing_fields']);
        $this->assertGreaterThan(0, $data['profile_completeness']);
    }

    // ---- Security Tests ----

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
        $this->putJson('/api/me/student', ['nis' => 'X'])->assertStatus(401);
    }

    public function test_mass_assignment_prevents_user_id_override(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $otherUser = User::factory()->create(['role' => 'student']);
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/me/student', [
                'school_id' => $school->id,
                'user_id' => $otherUser->id, // attempt to override
                'nis' => '12345',
            ])
            ->assertStatus(201);

        // user_id should be the authenticated user, not the overridden one
        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'nis' => '12345',
        ]);
        $this->assertDatabaseMissing('students', [
            'user_id' => $otherUser->id,
            'nis' => '12345',
        ]);
    }

    public function test_mass_assignment_prevents_role_override(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/me/student', [
                'school_id' => $school->id,
                'role' => 'admin', // attempt to escalate
                'nis' => '12345',
            ])
            ->assertStatus(201);

        // User role should remain 'student'
        $user->refresh();
        $this->assertEquals('student', $user->role);
    }

    public function test_validation_rejects_invalid_gender(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student', [
                'school_id' => $school->id,
                'gender' => 'invalid_gender',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('gender');
    }

    public function test_validation_rejects_invalid_school_id(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student', [
                'school_id' => 99999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('school_id');
    }

    public function test_validation_rejects_future_birth_date(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student', [
                'school_id' => $school->id,
                'birth_date' => '2030-01-01', // future date
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('birth_date');
    }

    public function test_validation_rejects_oversized_interests(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student', [
                'school_id' => $school->id,
                'interests' => str_repeat('a', 1001), // max 1000
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('interests');
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $this->getJson('/api/me/student')->assertStatus(401);
        $this->postJson('/api/me/student', [])->assertStatus(401);
        $this->putJson('/api/me/student', [])->assertStatus(401);
    }
}
