<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifikasi bahwa setelah registrasi, profile otomatis dibuat
 * sesuai role (guru, siswa, perusahaan).
 */
class RegistrationProfileTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $role, string $email): array
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => "Test {$role}",
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => $role,
        ]);

        $response->assertStatus(201);

        return [
            'response' => $response,
            'token' => $response->json('data.token'),
            'user' => User::where('email', $email)->first(),
        ];
    }

    public function test_company_profile_auto_created_after_registration(): void
    {
        $data = $this->register('company', 'autotest@company.com');
        $token = $data['token'];
        $user = $data['user'];

        // Company row harus sudah ada
        $this->assertNotNull($user->company, 'Company should be auto-created');
        $this->assertEquals('active', $user->company->status);

        // GET /api/me/company harus 200
        $this->withToken($token)
            ->getJson('/api/me/company')
            ->assertStatus(200);

        // Dashboard harus 200
        $this->withToken($token)
            ->getJson('/api/company/dashboard')
            ->assertStatus(200);
    }

    public function test_teacher_profile_auto_created_after_registration(): void
    {
        $data = $this->register('teacher', 'guruauto@teacher.com');
        $token = $data['token'];
        $user = $data['user'];

        // Teacher record harus sudah ada (skeleton)
        $this->assertNotNull($user->teacher, 'Teacher skeleton should be auto-created');
        $this->assertNull($user->teacher->school_id, 'School should be null until user completes profile');

        // GET /api/me/teacher harus 200
        $this->withToken($token)
            ->getJson('/api/me/teacher')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_student_profile_auto_created_after_registration(): void
    {
        $data = $this->register('student', 'siswaauto@student.com');
        $token = $data['token'];
        $user = $data['user'];

        // Student record harus sudah ada (skeleton)
        $this->assertNotNull($user->student, 'Student skeleton should be auto-created');
        $this->assertNull($user->student->school_id, 'School should be null until user completes profile');

        // GET /api/me/student harus 200
        $this->withToken($token)
            ->getJson('/api/me/student')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_company_can_complete_profile_after_registration(): void
    {
        $data = $this->register('company', 'complete@company.com');
        $token = $data['token'];

        // Complete profile
        $this->withToken($token)
            ->postJson('/api/me/company', [
                'name' => 'PT Complete Test',
                'industry' => 'Technology',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.profile.name', 'PT Complete Test');

        // Verify profile is complete
        $this->withToken($token)
            ->getJson('/api/me/company')
            ->assertStatus(200)
            ->assertJsonPath('data.profile.name', 'PT Complete Test');
    }

    public function test_teacher_can_complete_profile_after_registration(): void
    {
        $data = $this->register('teacher', 'gurucomplete@teacher.com');
        $token = $data['token'];

        $school = School::create(['name' => 'SMK Auto Test']);

        // Complete profile
        $this->withToken($token)
            ->postJson('/api/me/teacher', [
                'school_id' => $school->id,
                'nip' => '12345',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.school.name', 'SMK Auto Test');

        // Verify profile is complete
        $this->withToken($token)
            ->getJson('/api/me/teacher')
            ->assertStatus(200)
            ->assertJsonPath('data.school.name', 'SMK Auto Test');
    }

    public function test_student_can_complete_profile_after_registration(): void
    {
        $data = $this->register('student', 'siswacomplete@student.com');
        $token = $data['token'];

        $school = School::create(['name' => 'SMK Auto Student']);

        // Complete profile
        $this->withToken($token)
            ->postJson('/api/me/student', [
                'school_id' => $school->id,
                'nis' => '12345',
                'class' => 'XII RPL',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.school.name', 'SMK Auto Student');

        // Verify profile is complete
        $this->withToken($token)
            ->getJson('/api/me/student')
            ->assertStatus(200)
            ->assertJsonPath('data.nis', '12345');
    }
}
