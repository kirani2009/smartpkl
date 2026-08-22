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
 * Company mengelola pelamar: melihat, update status, jadwalkan interview.
 */
class CompanyApplicationTest extends TestCase
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

    protected function createInternship(): InternshipListing
    {
        return InternshipListing::create([
            'company_id' => $this->company->id,
            'title' => 'Web Developer Intern',
            'description' => 'Desc',
            'quota' => 3,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);
    }

    protected function makeApplication(InternshipListing $internship): Application
    {
        $school = School::create(['name' => 'SMK N 1']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = $studentUser->student()->create(['school_id' => $school->id]);

        return Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);
    }

    public function test_company_can_list_applications(): void
    {
        $internship = $this->createInternship();
        $this->makeApplication($internship);

        // Other company's application
        $otherUser = User::factory()->create(['role' => 'company']);
        $otherCompany = $otherUser->company()->create(['status' => 'active']);
        $otherInternship = InternshipListing::create([
            'company_id' => $otherCompany->id,
            'title' => 'Other',
            'description' => 'Other',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);
        $school = School::create(['name' => 'SMK N 2']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $otherStudentObj = $otherStudent->student()->create(['school_id' => $school->id]);
        Application::create([
            'internship_id' => $otherInternship->id,
            'student_id' => $otherStudentObj->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/company/applications')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_company_can_view_application_detail(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson("/api/company/applications/{$application->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'PENDING');
    }

    public function test_company_cannot_view_other_companies_application(): void
    {
        $otherUser = User::factory()->create(['role' => 'company']);
        $otherCompany = $otherUser->company()->create(['status' => 'active']);
        $otherInternship = InternshipListing::create([
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
            'internship_id' => $otherInternship->id,
            'student_id' => $studentObj->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson("/api/company/applications/{$application->id}")
            ->assertStatus(403);
    }

    // ---- Update Status ----

    public function test_company_can_review_application(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/applications/{$application->id}/status", [
                'status' => 'REVIEWED',
                'note' => 'Ditinjau oleh HR',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'REVIEWED');

        $this->assertDatabaseHas('application_status_histories', [
            'application_id' => $application->id,
            'status' => 'REVIEWED',
        ]);
    }

    public function test_company_can_accept_application(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);
        $application->update(['status' => 'REVIEWED']);

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/applications/{$application->id}/status", [
                'status' => 'ACCEPTED',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'ACCEPTED');
    }

    public function test_company_can_reject_application(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/applications/{$application->id}/status", [
                'status' => 'REJECTED',
                'note' => 'Tidak sesuai kriteria',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'REJECTED');
    }

    public function test_invalid_status_transition_rejected(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);

        $token = $this->companyToken();

        // PENDING -> INTERVIEW (tidak valid, harus REVIEWED dulu)
        $this->withToken($token)
            ->putJson("/api/company/applications/{$application->id}/status", [
                'status' => 'INTERVIEW',
            ])
            ->assertStatus(422);
    }

    public function test_rejects_invalid_status(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/applications/{$application->id}/status", [
                'status' => 'INVALID',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    // ---- Schedule Interview ----

    public function test_company_can_schedule_interview(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);
        $application->update(['status' => 'REVIEWED']);

        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson("/api/company/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(5)->toIso8601String(),
                'mode' => 'onsite',
                'location' => 'Kantor PT Tech, Lt. 3',
                'notes' => 'Bawa CV',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.mode', 'onsite');

        // Status should change to INTERVIEW
        $application->refresh();
        $this->assertEquals('INTERVIEW', $application->status);
    }

    public function test_company_cannot_schedule_interview_for_pending_application(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);

        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson("/api/company/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(5)->toIso8601String(),
                'mode' => 'onsite',
            ])
            ->assertStatus(422);
    }

    public function test_cannot_schedule_duplicate_active_interview(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);
        $application->update(['status' => 'REVIEWED']);

        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson("/api/company/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(5)->toIso8601String(),
                'mode' => 'online',
            ])
            ->assertStatus(201);

        $this->withToken($token)
            ->postJson("/api/company/applications/{$application->id}/interview", [
                'scheduled_at' => now()->addDays(6)->toIso8601String(),
                'mode' => 'online',
            ])
            ->assertStatus(409);
    }

    // ---- Auth ----

    public function test_non_company_cannot_access(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/company/applications')
            ->assertStatus(403);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/company/applications')->assertStatus(401);
    }
}
