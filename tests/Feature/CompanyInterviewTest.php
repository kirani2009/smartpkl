<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Interview;
use App\Models\InternshipListing;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 11 — Interview.
 * Company mengelola interview: daftar, lihat, selesai, batalkan.
 */
class CompanyInterviewTest extends TestCase
{
    use RefreshDatabase;

    private User $companyUser;
    private \App\Models\Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyUser = User::factory()->create(['role' => 'company']);
        $this->company = $this->companyUser->company()->create(['status' => 'active']);
        $this->company->profile()->create(['name' => 'PT Tech']);
    }

    private function companyToken(): string
    {
        return $this->companyUser->createToken('auth-token')->plainTextToken;
    }

    private function createInterview(string $interviewStatus = 'SCHEDULED'): array
    {
        $internship = InternshipListing::create([
            'company_id' => $this->company->id,
            'title' => 'Web Developer',
            'description' => 'Desc',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);

        $school = School::create(['name' => 'SMK N 1']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = $studentUser->student()->create(['school_id' => $school->id]);

        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => 'INTERVIEW',
            'applied_at' => now(),
        ]);

        $interview = Interview::create([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDays(3),
            'mode' => 'onsite',
            'location' => 'Kantor PT Tech',
            'notes' => 'Bawa CV',
            'status' => $interviewStatus,
        ]);

        return ['interview' => $interview, 'application' => $application, 'internship' => $internship, 'student' => $student];
    }

    // ---- List Interviews ----

    public function test_company_can_list_interviews(): void
    {
        $this->createInterview();

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/company/interviews')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_company_can_filter_interviews_by_status(): void
    {
        $this->createInterview('SCHEDULED');
        $this->createInterview('COMPLETED');

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/company/interviews?status=SCHEDULED')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', 'SCHEDULED');
    }

    // ---- View Detail ----

    public function test_company_can_view_interview_detail(): void
    {
        $data = $this->createInterview();
        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson("/api/company/interviews/{$data['interview']->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.mode', 'onsite')
            ->assertJsonPath('data.location', 'Kantor PT Tech');
    }

    public function test_company_cannot_view_other_companies_interview(): void
    {
        $otherUser = User::factory()->create(['role' => 'company']);
        $otherCompany = $otherUser->company()->create(['status' => 'active']);
        $internship = InternshipListing::create([
            'company_id' => $otherCompany->id,
            'title' => 'Other',
            'description' => 'Other',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);
        $school = School::create(['name' => 'SMK N 2']);
        $student = User::factory()->create(['role' => 'student']);
        $studentObj = $student->student()->create(['school_id' => $school->id]);
        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $studentObj->id,
            'status' => 'INTERVIEW',
            'applied_at' => now(),
        ]);
        $interview = Interview::create([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDays(3),
            'mode' => 'online',
            'status' => 'SCHEDULED',
        ]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson("/api/company/interviews/{$interview->id}")
            ->assertStatus(403);
    }

    // ---- Complete ----

    public function test_company_can_complete_interview(): void
    {
        $data = $this->createInterview();
        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/interviews/{$data['interview']->id}/complete")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'COMPLETED');
    }

    public function test_company_cannot_complete_already_completed_interview(): void
    {
        $data = $this->createInterview('COMPLETED');
        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/interviews/{$data['interview']->id}/complete")
            ->assertStatus(422);
    }

    // ---- Cancel ----

    public function test_company_can_cancel_interview(): void
    {
        $data = $this->createInterview();
        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/interviews/{$data['interview']->id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'CANCELLED');

        // Application status should revert to REVIEWED
        $data['application']->refresh();
        $this->assertEquals('REVIEWED', $data['application']->status);
    }

    public function test_company_cannot_cancel_already_cancelled_interview(): void
    {
        $data = $this->createInterview('CANCELLED');
        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/interviews/{$data['interview']->id}/cancel")
            ->assertStatus(422);
    }

    // ---- Auth ----

    public function test_non_company_cannot_access(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/company/interviews')
            ->assertStatus(403);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/company/interviews')->assertStatus(401);
    }
}
