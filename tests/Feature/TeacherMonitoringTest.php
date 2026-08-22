<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\InternshipListing;
use App\Models\Major;
use App\Models\School;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 12 — Teacher Monitoring.
 * Guru memantau siswa: daftar siswa, status penempatan, overview sekolah.
 */
class TeacherMonitoringTest extends TestCase
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

    private function createStudent(string $name = 'Siswa A'): \App\Models\Student
    {
        $user = User::factory()->create(['role' => 'student', 'name' => $name]);
        return $user->student()->create(['school_id' => $this->school->id]);
    }

    // ---- Overview ----

    public function test_teacher_can_view_overview(): void
    {
        $student = $this->createStudent();
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Dev',
            'description' => 'Desc',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);
        Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => 'ACCEPTED',
            'applied_at' => now(),
        ]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/monitoring/overview')
            ->assertStatus(200)
            ->assertJsonPath('data.school.name', 'SMK N 1')
            ->assertJsonPath('data.students.total', 1)
            ->assertJsonPath('data.students.placed', 1)
            ->assertJsonPath('data.applications.accepted', 1)
            ->assertJsonPath('data.students.placement_rate', 100);
    }

    // ---- Student List ----

    public function test_teacher_can_list_students(): void
    {
        $this->createStudent('Siswa A');
        $this->createStudent('Siswa B');

        // Other school's student
        $otherSchool = School::create(['name' => 'SMK N 2']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherStudent->student()->create(['school_id' => $otherSchool->id]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/monitoring/students')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_teacher_can_filter_by_placement(): void
    {
        $placedStudent = $this->createStudent('Placed');
        $unplacedStudent = $this->createStudent('Unplaced');

        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Dev',
            'description' => 'Desc',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);

        Application::create([
            'internship_id' => $internship->id,
            'student_id' => $placedStudent->id,
            'status' => 'ACCEPTED',
            'applied_at' => now(),
        ]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/monitoring/students?placement=placed')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.is_placed', true);

        $this->withToken($token)
            ->getJson('/api/teacher/monitoring/students?placement=unplaced')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.is_placed', false);
    }

    public function test_teacher_can_search_students(): void
    {
        $this->createStudent('Budi Santoso');
        $this->createStudent('Andi Wijaya');

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/monitoring/students?q=Budi')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.user.name', 'Budi Santoso');
    }

    // ---- Show Student Detail ----

    public function test_teacher_can_view_student_detail(): void
    {
        $student = $this->createStudent('Budi');

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson("/api/teacher/monitoring/students/{$student->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.user.name', 'Budi');
    }

    public function test_teacher_cannot_view_other_school_student(): void
    {
        $otherSchool = School::create(['name' => 'SMK N 2']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherStudentObj = $otherStudent->student()->create(['school_id' => $otherSchool->id]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson("/api/teacher/monitoring/students/{$otherStudentObj->id}")
            ->assertStatus(403);
    }

    // ---- Auth ----

    public function test_non_teacher_cannot_access(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/monitoring/overview')
            ->assertStatus(403);

        $this->withToken($token)
            ->getJson('/api/teacher/monitoring/students')
            ->assertStatus(403);
    }

    public function test_teacher_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/monitoring/overview')
            ->assertStatus(404);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/teacher/monitoring/overview')->assertStatus(401);
        $this->getJson('/api/teacher/monitoring/students')->assertStatus(401);
    }
}
