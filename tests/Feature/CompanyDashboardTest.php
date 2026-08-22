<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\InternshipListing;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 5 — Company.
 * Dashboard perusahaan: ringkasan data perusahaan.
 */
class CompanyDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function companyWithProfile(): User
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = $user->company()->create(['status' => 'active']);
        $company->profile()->create(['name' => 'PT Test Company']);
        return $user;
    }

    public function test_company_can_view_dashboard(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        // Create partnerships (different schools to avoid unique constraint)
        $school1 = School::create(['name' => 'SMK N 1']);
        $school2 = School::create(['name' => 'SMK N 2']);
        SchoolCompanyPartnership::create([
            'school_id' => $school1->id,
            'company_id' => $company->id,
            'status' => 'ACCEPTED',
        ]);
        SchoolCompanyPartnership::create([
            'school_id' => $school2->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($token)
            ->getJson('/api/company/dashboard')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.company.name', 'PT Test Company')
            ->assertJsonPath('data.partnerships.total', 2)
            ->assertJsonPath('data.partnerships.active', 1)
            ->assertJsonPath('data.partnerships.pending', 1);
    }

    public function test_dashboard_without_profile_returns_404(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/company/dashboard')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_non_company_cannot_access_dashboard(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/company/dashboard')
            ->assertStatus(403);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/company/dashboard')->assertStatus(401);
    }

    public function test_dashboard_includes_internship_stats(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        // Create internships
        InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Internship 1',
            'description' => 'Desc 1',
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);
        InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Internship 2',
            'description' => 'Desc 2',
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'DRAFT',
        ]);

        $this->withToken($token)
            ->getJson('/api/company/dashboard')
            ->assertStatus(200)
            ->assertJsonPath('data.internship_listings.total', 2)
            ->assertJsonPath('data.internship_listings.published', 1)
            ->assertJsonPath('data.internship_listings.draft', 1);
    }

    public function test_dashboard_includes_recent_applications(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        $school = School::create(['name' => 'SMK N 1']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = $studentUser->student()->create([
            'school_id' => $school->id,
            'major_id' => null,
        ]);

        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer Intern',
            'description' => 'Desc',
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);

        Application::create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $this->withToken($token)
            ->getJson('/api/company/dashboard')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.recent_applications')
            ->assertJsonPath('data.applicants.total', 1)
            ->assertJsonPath('data.applicants.pending', 1);
    }
}
