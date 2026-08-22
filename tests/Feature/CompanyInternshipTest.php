<?php

namespace Tests\Feature;

use App\Models\InternshipListing;
use App\Models\Major;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 7 — Internship.
 * Company CRUD lowongan PKL.
 */
class CompanyInternshipTest extends TestCase
{
    use RefreshDatabase;

    private User $companyUser;
    private \App\Models\Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyUser = User::factory()->create(['role' => 'company']);
        $this->company = $this->companyUser->company()->create(['status' => 'active']);
        $this->company->profile()->create(['name' => 'PT Tech Indonesia']);
    }

    private function companyToken(): string
    {
        return $this->companyUser->createToken('auth-token')->plainTextToken;
    }

    private function createInternship(array $overrides = []): InternshipListing
    {
        return InternshipListing::create(array_merge([
            'company_id' => $this->company->id,
            'title' => 'Web Developer Intern',
            'description' => 'Mengembangkan website perusahaan',
            'quota' => 3,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'DRAFT',
        ], $overrides));
    }

    // ---- CREATE ----

    public function test_company_can_create_internship(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/company/internships', [
                'title' => 'Mobile Developer Intern',
                'description' => 'Mengembangkan aplikasi mobile',
                'position' => 'Flutter Developer',
                'quota' => 2,
                'period_start' => now()->addDays(10)->toDateString(),
                'period_end' => now()->addDays(40)->toDateString(),
                'location' => 'Jakarta',
                'allowance' => 1500000,
                'facilities' => 'Laptop, Makan Siang',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Mobile Developer Intern')
            ->assertJsonPath('data.status', 'DRAFT')
            ->assertJsonPath('data.quota', 2);

        $this->assertDatabaseHas('internship_listings', [
            'company_id' => $this->company->id,
            'title' => 'Mobile Developer Intern',
        ]);
    }

    public function test_company_can_create_internship_with_requirements(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/company/internships', [
                'title' => 'Backend Developer',
                'description' => 'Mengembangkan API',
                'quota' => 1,
                'period_start' => now()->addDays(10)->toDateString(),
                'period_end' => now()->addDays(40)->toDateString(),
                'requirements' => ['Menguasai PHP', 'Menguasai Laravel', 'Paham MySQL'],
            ])
            ->assertStatus(201)
            ->assertJsonCount(3, 'data.requirements');

        $internship = InternshipListing::where('title', 'Backend Developer')->first();
        $this->assertCount(3, $internship->requirements);
    }

    public function test_company_can_create_internship_with_skills(): void
    {
        $skill1 = Skill::create(['name' => 'PHP']);
        $skill2 = Skill::create(['name' => 'Laravel']);

        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/company/internships', [
                'title' => 'Backend Developer',
                'description' => 'Mengembangkan API',
                'quota' => 1,
                'period_start' => now()->addDays(10)->toDateString(),
                'period_end' => now()->addDays(40)->toDateString(),
                'skill_ids' => [$skill1->id, $skill2->id],
            ])
            ->assertStatus(201)
            ->assertJsonCount(2, 'data.skills');
    }

    public function test_create_requires_title_and_description(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/company/internships', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'quota', 'period_start', 'period_end']);
    }

    public function test_create_rejects_invalid_skill_ids(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/company/internships', [
                'title' => 'Test',
                'description' => 'Test',
                'quota' => 1,
                'period_start' => now()->addDays(10)->toDateString(),
                'period_end' => now()->addDays(40)->toDateString(),
                'skill_ids' => [9999],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('skill_ids.0');
    }

    public function test_create_rejects_period_end_before_period_start(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/company/internships', [
                'title' => 'Test',
                'description' => 'Test',
                'quota' => 1,
                'period_start' => now()->addDays(40)->toDateString(),
                'period_end' => now()->addDays(10)->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('period_end');
    }

    public function test_create_with_school_requires_active_partnership(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/company/internships', [
                'title' => 'Test',
                'description' => 'Test',
                'quota' => 1,
                'period_start' => now()->addDays(10)->toDateString(),
                'period_end' => now()->addDays(40)->toDateString(),
                'school_id' => $school->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_create_with_school_and_active_partnership_succeeds(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $this->company->id,
            'status' => 'ACCEPTED',
        ]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/company/internships', [
                'title' => 'Test',
                'description' => 'Test',
                'quota' => 1,
                'period_start' => now()->addDays(10)->toDateString(),
                'period_end' => now()->addDays(40)->toDateString(),
                'school_id' => $school->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.school_id', $school->id);
    }

    // ---- READ ----

    public function test_company_can_list_own_internships(): void
    {
        $this->createInternship(['title' => 'Intern 1']);
        $this->createInternship(['title' => 'Intern 2']);

        // Other company's internship
        $otherUser = User::factory()->create(['role' => 'company']);
        $otherCompany = $otherUser->company()->create(['status' => 'active']);
        InternshipListing::create([
            'company_id' => $otherCompany->id,
            'title' => 'Other Intern',
            'description' => 'Other',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'DRAFT',
        ]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/company/internships')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_company_can_filter_internships_by_status(): void
    {
        $this->createInternship(['title' => 'Draft 1', 'status' => 'DRAFT']);
        $this->createInternship(['title' => 'Published 1', 'status' => 'PUBLISHED']);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/company/internships?status=PUBLISHED')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', 'PUBLISHED');
    }

    public function test_company_can_view_own_internship_detail(): void
    {
        $internship = $this->createInternship();
        $internship->requirements()->create(['description' => 'Menguasai PHP']);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson("/api/company/internships/{$internship->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Web Developer Intern')
            ->assertJsonCount(1, 'data.requirements');
    }

    public function test_company_cannot_view_other_companies_internship(): void
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
        ]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson("/api/company/internships/{$internship->id}")
            ->assertStatus(403);
    }

    // ---- UPDATE ----

    public function test_company_can_update_own_internship(): void
    {
        $internship = $this->createInternship();
        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/internships/{$internship->id}", [
                'title' => 'Updated Title',
                'quota' => 5,
                'status' => 'PUBLISHED',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.quota', 5)
            ->assertJsonPath('data.status', 'PUBLISHED');
    }

    public function test_company_can_update_requirements(): void
    {
        $internship = $this->createInternship();
        $internship->requirements()->create(['description' => 'Old Requirement']);

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/internships/{$internship->id}", [
                'requirements' => ['New Requirement 1', 'New Requirement 2'],
            ])
            ->assertStatus(200);

        $internship->refresh();
        $this->assertCount(2, $internship->requirements);
    }

    public function test_company_can_update_skills(): void
    {
        $skill1 = Skill::create(['name' => 'PHP']);
        $skill2 = Skill::create(['name' => 'Laravel']);
        $internship = $this->createInternship();

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/internships/{$internship->id}", [
                'skill_ids' => [$skill1->id, $skill2->id],
            ])
            ->assertStatus(200);

        $internship->refresh();
        $this->assertCount(2, $internship->skills);
    }

    public function test_company_cannot_update_other_companies_internship(): void
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
        ]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson("/api/company/internships/{$internship->id}", ['title' => 'Hacked'])
            ->assertStatus(403);
    }

    // ---- DELETE ----

    public function test_company_can_delete_own_internship_without_applications(): void
    {
        $internship = $this->createInternship();
        $token = $this->companyToken();

        $this->withToken($token)
            ->deleteJson("/api/company/internships/{$internship->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('internship_listings', ['id' => $internship->id]);
    }

    public function test_company_cannot_delete_internship_with_applications(): void
    {
        $internship = $this->createInternship();

        // Create a student and application
        $school = School::create(['name' => 'SMK N 1']);
        $studentUser = User::factory()->create(['role' => 'student']);
        $student = $studentUser->student()->create(['school_id' => $school->id]);
        $internship->applications()->create([
            'student_id' => $student->id,
            'status' => 'PENDING',
            'applied_at' => now(),
        ]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->deleteJson("/api/company/internships/{$internship->id}")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_company_cannot_delete_other_companies_internship(): void
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
        ]);

        $token = $this->companyToken();

        $this->withToken($token)
            ->deleteJson("/api/company/internships/{$internship->id}")
            ->assertStatus(403);
    }

    // ---- AUTH ----

    public function test_non_company_cannot_access_internship_endpoints(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/company/internships')
            ->assertStatus(403);

        $this->withToken($token)
            ->postJson('/api/company/internships', ['title' => 'Test'])
            ->assertStatus(403);
    }

    public function test_internship_endpoints_require_authentication(): void
    {
        $this->getJson('/api/company/internships')->assertStatus(401);
        $this->postJson('/api/company/internships', ['title' => 'Test'])->assertStatus(401);
    }

    public function test_company_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/company/internships')
            ->assertStatus(404);

        $this->withToken($token)
            ->postJson('/api/company/internships', [
                'title' => 'Test',
                'description' => 'Test',
                'quota' => 1,
                'period_start' => now()->addDays(10)->toDateString(),
                'period_end' => now()->addDays(40)->toDateString(),
            ])
            ->assertStatus(404);
    }
}
