<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\InternshipListing;
use App\Models\Major;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 15 — Admin Report tests.
 */
class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function adminToken(): string
    {
        return $this->admin->createToken('admin-token')->plainTextToken;
    }

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $this->getJson('/api/admin/reports/overview')
            ->assertStatus(401);
    }

    public function test_non_admin_cannot_access_reports(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $token = $teacher->createToken('teacher-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/admin/reports/overview')
            ->assertStatus(403);
    }

    public function test_overview_returns_platform_summary(): void
    {
        $school = School::create(['name' => 'SMK N 1', 'city' => 'Jakarta']);
        $company = User::factory()->create(['role' => 'company']);
        $companyModel = Company::create(['user_id' => $company->id]);
        CompanyProfile::create(['company_id' => $companyModel->id, 'name' => 'PT Tech']);

        $studentUser = User::factory()->create(['role' => 'student']);
        Student::create(['user_id' => $studentUser->id, 'school_id' => $school->id]);

        $this->withToken($this->adminToken())
            ->getJson('/api/admin/reports/overview')
            ->assertOk()
            ->assertJsonPath('data.users.total', fn ($v) => $v >= 1)
            ->assertJsonPath('data.schools.total', 1)
            ->assertJsonPath('data.companies.total', 1);
    }

    public function test_placement_report_returns_student_data(): void
    {
        $school = School::create(['name' => 'SMK N 1', 'city' => 'Jakarta']);
        $major = Major::create(['school_id' => $school->id, 'name' => 'RPL']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $school->id, 'major_id' => $major->id]);

        $this->withToken($this->adminToken())
            ->getJson('/api/admin/reports/placement')
            ->assertOk()
            ->assertJsonPath('data.summary.total_students', 1)
            ->assertJsonPath('data.summary.placed', 0);
    }

    public function test_placement_report_with_placed_student(): void
    {
        $school = School::create(['name' => 'SMK N 1', 'city' => 'Jakarta']);
        $major = Major::create(['school_id' => $school->id, 'name' => 'RPL']);

        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create(['user_id' => $companyUser->id]);
        CompanyProfile::create(['company_id' => $company->id, 'name' => 'PT Tech']);

        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $school->id, 'major_id' => $major->id]);

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

        $this->withToken($this->adminToken())
            ->getJson('/api/admin/reports/placement')
            ->assertOk()
            ->assertJsonPath('data.summary.placed', 1)
            ->assertJsonPath('data.summary.placement_rate', 100);
    }

    public function test_no_internship_report(): void
    {
        $school = School::create(['name' => 'SMK N 1', 'city' => 'Jakarta']);
        $studentUser = User::factory()->create(['role' => 'student']);
        Student::create(['user_id' => $studentUser->id, 'school_id' => $school->id]);

        $this->withToken($this->adminToken())
            ->getJson('/api/admin/reports/no-internship')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.by_status.belum_melamar', 1);
    }

    public function test_partnerships_report(): void
    {
        $school = School::create(['name' => 'SMK N 1', 'city' => 'Jakarta']);
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create(['user_id' => $companyUser->id]);

        SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($this->adminToken())
            ->getJson('/api/admin/reports/partnerships')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.pending', 1);
    }

    public function test_internships_report(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create(['user_id' => $companyUser->id]);

        InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer',
            'description' => 'Develop web apps',
            'quota' => 5,
            'period_start' => now()->addDays(14),
            'period_end' => now()->addDays(60),
            'status' => 'PUBLISHED',
        ]);

        $this->withToken($this->adminToken())
            ->getJson('/api/admin/reports/internships')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.published', 1);
    }

    public function test_applications_report(): void
    {
        $school = School::create(['name' => 'SMK N 1', 'city' => 'Jakarta']);
        $major = Major::create(['school_id' => $school->id, 'name' => 'RPL']);

        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create(['user_id' => $companyUser->id]);
        CompanyProfile::create(['company_id' => $company->id, 'name' => 'PT Tech']);

        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $school->id, 'major_id' => $major->id]);

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

        $this->withToken($this->adminToken())
            ->getJson('/api/admin/reports/applications')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.pending', 1);
    }

    public function test_placement_report_filter_by_school(): void
    {
        $school1 = School::create(['name' => 'SMK N 1', 'city' => 'Jakarta']);
        $school2 = School::create(['name' => 'SMK N 2', 'city' => 'Bandung']);

        $studentUser1 = User::factory()->create(['role' => 'student']);
        Student::create(['user_id' => $studentUser1->id, 'school_id' => $school1->id]);

        $studentUser2 = User::factory()->create(['role' => 'student']);
        Student::create(['user_id' => $studentUser2->id, 'school_id' => $school2->id]);

        $this->withToken($this->adminToken())
            ->getJson("/api/admin/reports/placement?school_id={$school1->id}")
            ->assertOk()
            ->assertJsonPath('data.summary.total_students', 1);
    }
}
