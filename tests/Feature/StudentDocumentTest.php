<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — Student Profile.
 * Dokumen siswa (CV, portofolio, dll).
 */
class StudentDocumentTest extends TestCase
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

    public function test_student_can_add_document(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student/documents', [
                'type' => 'CV',
                'title' => 'CV Saya',
                'file_path' => 'documents/cv-saya.pdf',
                'file_size' => 1024000,
                'mime_type' => 'application/pdf',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.type', 'CV')
            ->assertJsonPath('data.title', 'CV Saya');

        $this->assertDatabaseHas('documents', [
            'student_id' => $this->studentUser->student->id,
            'type' => 'CV',
        ]);
    }

    public function test_student_can_list_documents(): void
    {
        $this->studentUser->student->documents()->create([
            'type' => 'CV',
            'title' => 'CV',
            'file_path' => 'cv.pdf',
        ]);
        $this->studentUser->student->documents()->create([
            'type' => 'PORTFOLIO',
            'title' => 'Portfolio',
            'file_path' => 'portfolio.pdf',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/me/student/documents')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_student_can_filter_documents_by_type(): void
    {
        $this->studentUser->student->documents()->create([
            'type' => 'CV',
            'title' => 'CV',
            'file_path' => 'cv.pdf',
        ]);
        $this->studentUser->student->documents()->create([
            'type' => 'PORTFOLIO',
            'title' => 'Portfolio',
            'file_path' => 'portfolio.pdf',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/me/student/documents?type=CV')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.type', 'CV');
    }

    public function test_student_can_delete_document(): void
    {
        $document = $this->studentUser->student->documents()->create([
            'type' => 'CV',
            'title' => 'CV',
            'file_path' => 'cv.pdf',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->deleteJson("/api/me/student/documents/{$document->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_student_cannot_access_other_students_document(): void
    {
        $otherUser = User::factory()->create(['role' => 'student']);
        $otherSchool = School::create(['name' => 'SMK N 2']);
        $otherStudent = $otherUser->student()->create(['school_id' => $otherSchool->id]);
        $document = $otherStudent->documents()->create([
            'type' => 'CV',
            'title' => 'Other CV',
            'file_path' => 'other.pdf',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->deleteJson("/api/me/student/documents/{$document->id}")
            ->assertStatus(403);
    }

    public function test_create_requires_type_and_title_and_file_path(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student/documents', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'title', 'file_path']);
    }

    public function test_create_rejects_invalid_type(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student/documents', [
                'type' => 'INVALID',
                'title' => 'Test',
                'file_path' => 'test.pdf',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    public function test_student_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me/student/documents')
            ->assertStatus(404);
    }
}
