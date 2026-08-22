<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\InternshipListing;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 10 — Selection.
 * Fitur lanjutan seleksi: statistik, rating, batch operations.
 */
class CompanySelectionTest extends TestCase
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

    private function createInternship(): InternshipListing
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

    private function makeApplication(InternshipListing $internship, string $status = 'PENDING'): Application
    {
        $school = School::create(['name' => 'SMK N 1']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = $studentUser->student()->create(['school_id' => $school->id]);

        return Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => $status,
            'applied_at' => now(),
        ]);
    }

    // ---- Stats ----

    public function test_company_can_view_selection_stats(): void
    {
        $internship = $this->createInternship();
        $this->makeApplication($internship, 'PENDING');
        $this->makeApplication($internship, 'PENDING');
        $this->makeApplication($internship, 'ACCEPTED');

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/company/selection/stats')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.applications.total', 3)
            ->assertJsonPath('data.items.0.applications.pending', 2)
            ->assertJsonPath('data.items.0.applications.accepted', 1)
            ->assertJsonPath('data.items.0.quota', 3);
    }

    public function test_stats_only_shows_own_company_internships(): void
    {
        $internship = $this->createInternship();
        $this->makeApplication($internship);

        // Other company's internship
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
        $this->makeApplication($otherInternship);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/company/selection/stats')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    // ---- Rate ----

    public function test_company_can_rate_application(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/applications/{$application->id}/rate", [
                'rating' => 4,
                'selection_notes' => 'Kandidat kuat, skill PHP bagus.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.rating', 4);

        $this->assertDatabaseHas('applications', [
            'id' => $application->id,
            'rating' => 4,
            'selection_notes' => 'Kandidat kuat, skill PHP bagus.',
        ]);
    }

    public function test_rate_rejects_invalid_rating(): void
    {
        $internship = $this->createInternship();
        $application = $this->makeApplication($internship);

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/applications/{$application->id}/rate", [
                'rating' => 6,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('rating');
    }

    public function test_company_cannot_rate_other_companies_application(): void
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
        $application = $this->makeApplication($otherInternship);

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/applications/{$application->id}/rate", [
                'rating' => 5,
            ])
            ->assertStatus(403);
    }

    // ---- Batch Update ----

    public function test_company_can_batch_update_applications(): void
    {
        $internship = $this->createInternship();
        $app1 = $this->makeApplication($internship, 'PENDING');
        $app2 = $this->makeApplication($internship, 'PENDING');
        $app3 = $this->makeApplication($internship, 'PENDING');

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson('/api/company/selection/batch', [
                'application_ids' => [$app1->id, $app2->id],
                'status' => 'REJECTED',
                'note' => 'Tidak sesuai kriteria',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.updated', 2)
            ->assertJsonPath('data.skipped', 0);

        $this->assertDatabaseHas('applications', ['id' => $app1->id, 'status' => 'REJECTED']);
        $this->assertDatabaseHas('applications', ['id' => $app2->id, 'status' => 'REJECTED']);
        $this->assertDatabaseHas('applications', ['id' => $app3->id, 'status' => 'PENDING']); // unchanged
    }

    public function test_batch_update_skips_invalid_transitions(): void
    {
        $internship = $this->createInternship();
        $pendingApp = $this->makeApplication($internship, 'PENDING');
        $acceptedApp = $this->makeApplication($internship, 'ACCEPTED');

        $token = $this->companyToken();

        // Try to change ACCEPTED to REVIEWED (invalid transition)
        $this->withToken($token)
            ->putJson('/api/company/selection/batch', [
                'application_ids' => [$pendingApp->id, $acceptedApp->id],
                'status' => 'REVIEWED',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.updated', 1)
            ->assertJsonPath('data.skipped', 1);
    }

    public function test_batch_update_only_affects_own_company_applications(): void
    {
        $internship = $this->createInternship();
        $myApp = $this->makeApplication($internship, 'PENDING');

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
        $otherApp = $this->makeApplication($otherInternship, 'PENDING');

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson('/api/company/selection/batch', [
                'application_ids' => [$myApp->id, $otherApp->id],
                'status' => 'REJECTED',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.updated', 1);

        $this->assertDatabaseHas('applications', ['id' => $myApp->id, 'status' => 'REJECTED']);
        $this->assertDatabaseHas('applications', ['id' => $otherApp->id, 'status' => 'PENDING']); // unchanged
    }

    public function test_batch_update_rejects_empty_array(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson('/api/company/selection/batch', [
                'application_ids' => [],
                'status' => 'REJECTED',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('application_ids');
    }

    // ---- Top Applicants ----

    public function test_company_can_view_top_applicants(): void
    {
        $internship = $this->createInternship();
        $app1 = $this->makeApplication($internship);
        $app2 = $this->makeApplication($internship);
        $app3 = $this->makeApplication($internship);

        $app2->update(['rating' => 5]);
        $app1->update(['rating' => 3]);
        $app3->update(['rating' => 4]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/company/selection/top-applicants')
            ->assertStatus(200)
            ->assertJsonCount(3, 'data.items')
            ->assertJsonPath('data.items.0.rating', 5) // highest first
            ->assertJsonPath('data.items.1.rating', 4)
            ->assertJsonPath('data.items.2.rating', 3);
    }

    public function test_top_applicants_only_shows_rated(): void
    {
        $internship = $this->createInternship();
        $rated = $this->makeApplication($internship);
        $rated->update(['rating' => 4]);
        $this->makeApplication($internship); // no rating

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/company/selection/top-applicants')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    // ---- Auth ----

    public function test_non_company_cannot_access(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/company/selection/stats')
            ->assertStatus(403);

        $this->withToken($token)
            ->getJson('/api/company/selection/top-applicants')
            ->assertStatus(403);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/company/selection/stats')->assertStatus(401);
        $this->getJson('/api/company/selection/top-applicants')->assertStatus(401);
        $this->putJson('/api/company/selection/batch', [])->assertStatus(401);
    }
}
