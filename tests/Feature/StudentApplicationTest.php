<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\InternshipListing;
use App\Models\Major;
use App\Models\SavedInternship;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 9 — Application.
 * Siswa menjelajahi lowongan, melamar, melihat lamaran, menyimpan lowongan.
 */
class StudentApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private \App\Models\Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::create(['name' => 'SMK N 1']);
        $this->studentUser = User::factory()->create(['role' => 'student']);
        $this->student = $this->studentUser->student()->create(['school_id' => $school->id]);
    }

    private function studentToken(): string
    {
        return $this->studentUser->createToken('auth-token')->plainTextToken;
    }

    protected function createPublishedInternship(array $overrides = []): InternshipListing
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $company->profile()->create(['name' => 'PT Tech']);

        return InternshipListing::create(array_merge([
            'company_id' => $company->id,
            'title' => 'Web Developer Intern',
            'description' => 'Mengembangkan website',
            'quota' => 3,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ], $overrides));
    }

    // ---- Browse Internships ----

    public function test_student_can_list_published_internships(): void
    {
        $this->createPublishedInternship();
        $this->createPublishedInternship(['title' => 'Mobile Dev Intern']);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/student/internships')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_student_cannot_see_draft_internships(): void
    {
        $this->createPublishedInternship();
        $this->createPublishedInternship(['title' => 'Draft Intern', 'status' => 'DRAFT']);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/student/internships')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_student_can_view_internship_detail(): void
    {
        $internship = $this->createPublishedInternship();

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/student/internships/{$internship->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Web Developer Intern');
    }

    public function test_student_cannot_view_draft_internship_detail(): void
    {
        $internship = $this->createPublishedInternship(['status' => 'DRAFT']);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/student/internships/{$internship->id}")
            ->assertStatus(404);
    }

    // ---- Apply ----

    public function test_student_can_apply_to_internship(): void
    {
        $internship = $this->createPublishedInternship();
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson("/api/student/internships/{$internship->id}/apply", [
                'message' => 'Saya tertarik dengan lowongan ini.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PENDING');

        $this->assertDatabaseHas('applications', [
            'internship_id' => $internship->id,
            'student_id' => $this->student->id,
            'status' => 'PENDING',
        ]);

        // Cek status history
        $application = Application::where('internship_id', $internship->id)
            ->where('student_id', $this->student->id)
            ->first();
        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'status' => 'PENDING',
        ]);
    }

    public function test_student_cannot_apply_twice(): void
    {
        $internship = $this->createPublishedInternship();
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson("/api/student/internships/{$internship->id}/apply")
            ->assertStatus(201);

        $this->withToken($token)
            ->postJson("/api/student/internships/{$internship->id}/apply")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_student_cannot_apply_to_draft_internship(): void
    {
        $internship = $this->createPublishedInternship(['status' => 'DRAFT']);
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson("/api/student/internships/{$internship->id}/apply")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ---- My Applications ----

    public function test_student_can_list_own_applications(): void
    {
        $internship1 = $this->createPublishedInternship();
        $internship2 = $this->createPublishedInternship(['title' => 'Other']);

        Application::create([
            'internship_id' => $internship1->id,
            'student_id' => $this->student->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        // Other student's application
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherStudent->student()->create(['school_id' => $this->student->school_id]);
        Application::create([
            'internship_id' => $internship2->id,
            'student_id' => $otherStudent->student->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/student/applications')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_student_can_view_own_application_detail(): void
    {
        $internship = $this->createPublishedInternship();
        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $this->student->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/student/applications/{$application->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'PENDING');
    }

    public function test_student_cannot_view_other_students_application(): void
    {
        $internship = $this->createPublishedInternship();
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherStudentObj = $otherStudent->student()->create(['school_id' => $this->student->school_id]);
        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $otherStudentObj->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/student/applications/{$application->id}")
            ->assertStatus(403);
    }

    // ---- Save / Unsave ----

    public function test_student_can_save_internship(): void
    {
        $internship = $this->createPublishedInternship();
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson("/api/student/internships/{$internship->id}/save")
            ->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('saved_internships', [
            'student_id' => $this->student->id,
            'internship_id' => $internship->id,
        ]);
    }

    public function test_student_cannot_save_twice(): void
    {
        $internship = $this->createPublishedInternship();
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson("/api/student/internships/{$internship->id}/save")
            ->assertStatus(201);

        $this->withToken($token)
            ->postJson("/api/student/internships/{$internship->id}/save")
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_student_can_unsave_internship(): void
    {
        $internship = $this->createPublishedInternship();
        SavedInternship::create([
            'student_id' => $this->student->id,
            'internship_id' => $internship->id,
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->deleteJson("/api/student/internships/{$internship->id}/save")
            ->assertStatus(200);

        $this->assertDatabaseMissing('saved_internships', [
            'student_id' => $this->student->id,
            'internship_id' => $internship->id,
        ]);
    }

    public function test_student_can_list_saved_internships(): void
    {
        $internship = $this->createPublishedInternship();
        SavedInternship::create([
            'student_id' => $this->student->id,
            'internship_id' => $internship->id,
        ]);

        $token = $this->studentToken();

        $response = $this->withToken($token)
            ->getJson('/api/student/saved-internships')
            ->assertStatus(200);

        $data = $response->json('data.items.data');
        $this->assertCount(1, $data);
    }

    // ---- Auth ----

    public function test_student_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/student/internships')
            ->assertStatus(404);
    }

    public function test_non_student_cannot_access(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $token = $teacher->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/student/internships')
            ->assertStatus(403);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/student/internships')->assertStatus(401);
        $this->getJson('/api/student/applications')->assertStatus(401);
        $this->getJson('/api/student/saved-internships')->assertStatus(401);
    }
}
