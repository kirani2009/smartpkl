<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\InternshipListing;
use App\Models\Major;
use App\Models\School;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 15 — Teacher Report tests.
 */
class TeacherReportTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'SMK N 1', 'city' => 'Jakarta']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        Teacher::create(['user_id' => $this->teacher->id, 'school_id' => $this->school->id]);
    }

    private function teacherToken(): string
    {
        return $this->teacher->createToken('teacher-token')->plainTextToken;
    }

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $this->getJson('/api/teacher/reports/placement')
            ->assertStatus(401);
    }

    public function test_teacher_without_profile_gets_404(): void
    {
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $token = $otherTeacher->createToken('other-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/reports/placement')
            ->assertStatus(404);
    }

    public function test_placement_report_returns_school_data(): void
    {
        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL']);
        $studentUser = User::factory()->create(['role' => 'student']);
        Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id, 'major_id' => $major->id]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/teacher/reports/placement')
            ->assertOk()
            ->assertJsonPath('data.school', 'SMK N 1')
            ->assertJsonPath('data.summary.total_students', 1)
            ->assertJsonPath('data.summary.placed', 0);
    }

    public function test_placement_report_only_shows_own_school(): void
    {
        $otherSchool = School::create(['name' => 'SMK N 2', 'city' => 'Bandung']);

        $studentUser = User::factory()->create(['role' => 'student']);
        Student::create(['user_id' => $studentUser->id, 'school_id' => $otherSchool->id]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/teacher/reports/placement')
            ->assertOk()
            ->assertJsonPath('data.summary.total_students', 0);
    }

    public function test_no_internship_report(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/teacher/reports/no-internship')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.by_status.belum_melamar', 1);
    }

    public function test_no_internship_report_with_applied_student(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create(['user_id' => $companyUser->id]);

        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id, 'major_id' => $major->id]);

        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer',
            'description' => 'Develop web apps',
            'quota' => 5,
            'period_start' => now()->addDays(14),
            'period_end' => now()->addDays(60),
            'status' => 'PUBLISHED',
        ]);

        Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => 'REJECTED',
            'applied_at' => now(),
        ]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/teacher/reports/no-internship')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.by_status.belum_diterima', 1);
    }

    public function test_applications_report(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create(['user_id' => $companyUser->id]);
        CompanyProfile::create(['company_id' => $company->id, 'name' => 'PT Tech']);

        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id, 'major_id' => $major->id]);

        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer',
            'description' => 'Develop web apps',
            'quota' => 5,
            'period_start' => now()->addDays(14),
            'period_end' => now()->addDays(60),
            'status' => 'PUBLISHED',
        ]);

        Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/teacher/reports/applications')
            ->assertOk()
            ->assertJsonPath('data.school', 'SMK N 1')
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.pending', 1);
    }

    public function test_applications_report_filter_by_status(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create(['user_id' => $companyUser->id]);

        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id]);

        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer',
            'description' => 'Develop web apps',
            'quota' => 5,
            'period_start' => now()->addDays(14),
            'period_end' => now()->addDays(60),
            'status' => 'PUBLISHED',
        ]);

        Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => 'ACCEPTED',
            'applied_at' => now(),
        ]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/teacher/reports/applications?status=ACCEPTED')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.accepted', 1);

        $this->withToken($this->teacherToken())
            ->getJson('/api/teacher/reports/applications?status=PENDING')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 0);
    }

    public function test_placement_report_with_placed_student(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create(['user_id' => $companyUser->id]);
        CompanyProfile::create(['company_id' => $company->id, 'name' => 'PT Tech']);

        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id, 'major_id' => $major->id]);

        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer',
            'description' => 'Develop web apps',
            'quota' => 5,
            'period_start' => now()->addDays(14),
            'period_end' => now()->addDays(60),
            'status' => 'PUBLISHED',
        ]);

        Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => 'ACCEPTED',
            'applied_at' => now(),
        ]);

        $this->withToken($this->teacherToken())
            ->getJson('/api/teacher/reports/placement')
            ->assertOk()
            ->assertJsonPath('data.summary.placed', 1)
            ->assertJsonPath('data.summary.placement_rate', 100);
    }
}
