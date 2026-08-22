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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 15 — Company Report tests.
 */
class CompanyReportTest extends TestCase
{
    use RefreshDatabase;

    private User $companyUser;
    private Company $company;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'SMK N 1', 'city' => 'Jakarta']);
        $this->companyUser = User::factory()->create(['role' => 'company']);
        $this->company = Company::create(['user_id' => $this->companyUser->id]);
        CompanyProfile::create(['company_id' => $this->company->id, 'name' => 'PT Tech']);
    }

    private function companyToken(): string
    {
        return $this->companyUser->createToken('company-token')->plainTextToken;
    }

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $this->getJson('/api/company/reports/applications')
            ->assertStatus(401);
    }

    public function test_applications_report_returns_company_data(): void
    {
        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id, 'major_id' => $major->id]);

        $internship = InternshipListing::create([
            'company_id' => $this->company->id,
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

        $this->withToken($this->companyToken())
            ->getJson('/api/company/reports/applications')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.pending', 1);
    }

    public function test_applications_report_only_shows_own_company(): void
    {
        $otherCompanyUser = User::factory()->create(['role' => 'company']);
        $otherCompany = Company::create(['user_id' => $otherCompanyUser->id]);

        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id]);

        $internship = InternshipListing::create([
            'company_id' => $otherCompany->id,
            'title' => 'Other Developer',
            'description' => 'Other apps',
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

        $this->withToken($this->companyToken())
            ->getJson('/api/company/reports/applications')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 0);
    }

    public function test_internships_report(): void
    {
        InternshipListing::create([
            'company_id' => $this->company->id,
            'title' => 'Web Developer',
            'description' => 'Develop web apps',
            'quota' => 5,
            'period_start' => now()->addDays(14),
            'period_end' => now()->addDays(60),
            'status' => 'PUBLISHED',
        ]);

        InternshipListing::create([
            'company_id' => $this->company->id,
            'title' => 'Mobile Developer',
            'description' => 'Develop mobile apps',
            'quota' => 3,
            'period_start' => now()->addDays(14),
            'period_end' => now()->addDays(60),
            'status' => 'DRAFT',
        ]);

        $this->withToken($this->companyToken())
            ->getJson('/api/company/reports/internships')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 2)
            ->assertJsonPath('data.summary.published', 1)
            ->assertJsonPath('data.summary.draft', 1);
    }

    public function test_internships_report_only_shows_own_company(): void
    {
        $otherCompanyUser = User::factory()->create(['role' => 'company']);
        $otherCompany = Company::create(['user_id' => $otherCompanyUser->id]);

        InternshipListing::create([
            'company_id' => $otherCompany->id,
            'title' => 'Other Developer',
            'description' => 'Other apps',
            'quota' => 5,
            'period_start' => now()->addDays(14),
            'period_end' => now()->addDays(60),
            'status' => 'PUBLISHED',
        ]);

        $this->withToken($this->companyToken())
            ->getJson('/api/company/reports/internships')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 0);
    }

    public function test_partnerships_report(): void
    {
        SchoolCompanyPartnership::create([
            'school_id' => $this->school->id,
            'company_id' => $this->company->id,
            'status' => 'ACCEPTED',
        ]);

        $this->withToken($this->companyToken())
            ->getJson('/api/company/reports/partnerships')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.active', 1);
    }

    public function test_partnerships_report_only_shows_own_company(): void
    {
        $otherCompanyUser = User::factory()->create(['role' => 'company']);
        $otherCompany = Company::create(['user_id' => $otherCompanyUser->id]);

        SchoolCompanyPartnership::create([
            'school_id' => $this->school->id,
            'company_id' => $otherCompany->id,
            'status' => 'ACCEPTED',
        ]);

        $this->withToken($this->companyToken())
            ->getJson('/api/company/reports/partnerships')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 0);
    }

    public function test_applications_report_filter_by_status(): void
    {
        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id, 'major_id' => $major->id]);

        $internship = InternshipListing::create([
            'company_id' => $this->company->id,
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

        $this->withToken($this->companyToken())
            ->getJson('/api/company/reports/applications?status=ACCEPTED')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.accepted', 1);

        $this->withToken($this->companyToken())
            ->getJson('/api/company/reports/applications?status=PENDING')
            ->assertOk()
            ->assertJsonPath('data.summary.total', 0);
    }

    public function test_internships_report_shows_filled_count(): void
    {
        $major = Major::create(['school_id' => $this->school->id, 'name' => 'RPL']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create(['user_id' => $studentUser->id, 'school_id' => $this->school->id, 'major_id' => $major->id]);

        $internship = InternshipListing::create([
            'company_id' => $this->company->id,
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

        $response = $this->withToken($this->companyToken())
            ->getJson('/api/company/reports/internships')
            ->assertOk();

        $internshipData = $response->json('data.internships.0');
        $this->assertEquals(5, $internshipData['quota']);
        $this->assertEquals(1, $internshipData['filled']);
        $this->assertEquals(4, $internshipData['remaining']);
    }
}
