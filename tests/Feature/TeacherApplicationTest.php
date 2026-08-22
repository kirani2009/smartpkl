<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\InternshipListing;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 9 — Application.
 * Guru memantau lamaran siswa dari sekolahnya.
 */
class TeacherApplicationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'SMK N 1']);
        $this->teacherUser = User::factory()->create(['role' => 'teacher']);
        $this->teacherUser->teacher()->create(['school_id' => $this->school->id]);
    }

    private function teacherToken(): string
    {
        return $this->teacherUser->createToken('auth-token')->plainTextToken;
    }

    protected function createStudentApplication(): Application
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = $studentUser->student()->create(['school_id' => $this->school->id]);

        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer',
            'description' => 'Desc',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);

        return Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);
    }

    public function test_teacher_can_list_student_applications(): void
    {
        $this->createStudentApplication();

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/applications')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_teacher_only_sees_own_school_applications(): void
    {
        $this->createStudentApplication();

        // Other school's application
        $otherSchool = School::create(['name' => 'SMK N 2']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherStudentObj = $otherStudent->student()->create(['school_id' => $otherSchool->id]);
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Other',
            'description' => 'Other',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);
        Application::create([
            'internship_id' => $internship->id,
            'student_id' => $otherStudentObj->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/applications')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_teacher_can_view_student_application_detail(): void
    {
        $application = $this->createStudentApplication();

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson("/api/teacher/applications/{$application->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'PENDING');
    }

    public function test_teacher_cannot_view_other_school_application(): void
    {
        $otherSchool = School::create(['name' => 'SMK N 2']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherStudentObj = $otherStudent->student()->create(['school_id' => $otherSchool->id]);
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Other',
            'description' => 'Other',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);
        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $otherStudentObj->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson("/api/teacher/applications/{$application->id}")
            ->assertStatus(403);
    }

    public function test_teacher_can_view_stats(): void
    {
        $this->createStudentApplication();

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/applications/stats')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.pending', 1);
    }

    public function test_teacher_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/applications')
            ->assertStatus(404);
    }

    public function test_non_teacher_cannot_access(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/applications')
            ->assertStatus(403);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/teacher/applications')->assertStatus(401);
        $this->getJson('/api/teacher/applications/stats')->assertStatus(401);
    }
}
