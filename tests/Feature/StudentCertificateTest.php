<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — Student Profile.
 * Sertifikat siswa (CRUD).
 */
class StudentCertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::create(['name' => 'SMK N 1']);
        $this->studentUser = User::factory()->create(['role' => 'student']);
        $this->studentUser->student()->create(['school_id' => $school->id]);
    }

    private function studentToken(): string
    {
        return $this->studentUser->createToken('auth-token')->plainTextToken;
    }

    public function test_student_can_create_certificate(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student/certificates', [
                'title' => 'Sertifikat Web Development',
                'issuer' => 'Google',
                'issued_at' => '2025-01-15',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Sertifikat Web Development')
            ->assertJsonPath('data.issuer', 'Google');

        $this->assertDatabaseHas('certificates', [
            'student_id' => $this->studentUser->student->id,
            'title' => 'Sertifikat Web Development',
        ]);
    }

    public function test_student_can_list_certificates(): void
    {
        $this->studentUser->student->certificates()->create([
            'title' => 'Cert 1',
            'issued_at' => '2025-01-01',
        ]);
        $this->studentUser->student->certificates()->create([
            'title' => 'Cert 2',
            'issued_at' => '2025-06-01',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/me/student/certificates')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_student_can_view_own_certificate_detail(): void
    {
        $certificate = $this->studentUser->student->certificates()->create([
            'title' => 'My Certificate',
            'issuer' => 'Microsoft',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/me/student/certificates/{$certificate->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'My Certificate');
    }

    public function test_student_can_update_certificate(): void
    {
        $certificate = $this->studentUser->student->certificates()->create([
            'title' => 'Old Title',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->putJson("/api/me/student/certificates/{$certificate->id}", [
                'title' => 'New Title',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'New Title');
    }

    public function test_student_can_delete_certificate(): void
    {
        $certificate = $this->studentUser->student->certificates()->create([
            'title' => 'To Delete',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->deleteJson("/api/me/student/certificates/{$certificate->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('certificates', ['id' => $certificate->id]);
    }

    public function test_student_cannot_access_other_students_certificate(): void
    {
        $otherUser = User::factory()->create(['role' => 'student']);
        $otherSchool = School::create(['name' => 'SMK N 2']);
        $otherStudent = $otherUser->student()->create(['school_id' => $otherSchool->id]);
        $certificate = $otherStudent->certificates()->create(['title' => 'Other']);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/me/student/certificates/{$certificate->id}")
            ->assertStatus(403);
    }

    public function test_create_requires_title(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student/certificates', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_student_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me/student/certificates')
            ->assertStatus(404);

        $this->withToken($token)
            ->postJson('/api/me/student/certificates', ['title' => 'Test'])
            ->assertStatus(404);
    }
}
