<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 6 — Partnership (sisi guru).
 * Guru mencari perusahaan, mengirim request, melihat daftar partnership.
 */
class TeacherPartnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'SMK N 1 Bandung']);
        $this->teacherUser = User::factory()->create(['role' => 'teacher']);
        $this->teacherUser->teacher()->create(['school_id' => $this->school->id]);
    }

    private function teacherToken(): string
    {
        return $this->teacherUser->createToken('auth-token')->plainTextToken;
    }

    private function createCompany(string $name = 'PT Tech', string $city = 'Jakarta'): Company
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = $user->company()->create(['status' => 'active']);
        $company->profile()->create([
            'name' => $name,
            'industry' => 'Technology',
            'city' => $city,
        ]);
        return $company;
    }

    // ---- Search Companies ----

    public function test_teacher_can_search_companies(): void
    {
        $this->createCompany('PT Alpha', 'Bandung');
        $this->createCompany('PT Beta', 'Jakarta');

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships/companies')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_teacher_can_search_companies_by_name(): void
    {
        $this->createCompany('PT Alpha');
        $this->createCompany('PT Beta');

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships/companies?q=Alpha')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.profile.name', 'PT Alpha');
    }

    public function test_teacher_can_search_companies_by_city(): void
    {
        $this->createCompany('PT Alpha', 'Bandung');
        $this->createCompany('PT Beta', 'Jakarta');

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships/companies?city=Bandung')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.profile.city', 'Bandung');
    }

    public function test_inactive_companies_not_shown_in_search(): void
    {
        $activeUser = User::factory()->create(['role' => 'company']);
        $activeCompany = $activeUser->company()->create(['status' => 'active']);
        $activeCompany->profile()->create(['name' => 'PT Active']);

        $suspendedUser = User::factory()->create(['role' => 'company']);
        $suspendedCompany = $suspendedUser->company()->create(['status' => 'suspended']);
        $suspendedCompany->profile()->create(['name' => 'PT Suspended']);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships/companies')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.profile.name', 'PT Active');
    }

    public function test_search_companies_requires_teacher_profile(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships/companies')
            ->assertStatus(404);
    }

    // ---- Send Partnership Request ----

    public function test_teacher_can_send_partnership_request(): void
    {
        $company = $this->createCompany();
        $token = $this->teacherToken();

        $this->withToken($token)
            ->postJson('/api/teacher/partnerships', [
                'company_id' => $company->id,
                'notes' => 'Kami ingin menjalin kerja sama PKL.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.school_id', $this->school->id)
            ->assertJsonPath('data.company_id', $company->id);

        $this->assertDatabaseHas('school_company_partnerships', [
            'school_id' => $this->school->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
            'requested_by' => $this->teacherUser->id,
        ]);
    }

    public function test_teacher_cannot_send_duplicate_partnership(): void
    {
        $company = $this->createCompany();
        $token = $this->teacherToken();

        SchoolCompanyPartnership::create([
            'school_id' => $this->school->id,
            'company_id' => $company->id,
            'requested_by' => $this->teacherUser->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($token)
            ->postJson('/api/teacher/partnerships', [
                'company_id' => $company->id,
            ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_teacher_can_resend_after_rejection(): void
    {
        $company = $this->createCompany();
        $token = $this->teacherToken();

        SchoolCompanyPartnership::create([
            'school_id' => $this->school->id,
            'company_id' => $company->id,
            'requested_by' => $this->teacherUser->id,
            'status' => 'REJECTED',
        ]);

        $this->withToken($token)
            ->postJson('/api/teacher/partnerships', [
                'company_id' => $company->id,
                'notes' => 'Mohon pertimbangan kembali.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.status', 'PENDING');
    }

    public function test_send_request_requires_valid_company(): void
    {
        $token = $this->teacherToken();

        $this->withToken($token)
            ->postJson('/api/teacher/partnerships', [
                'company_id' => 9999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('company_id');
    }

    public function test_send_request_to_suspended_company_fails(): void
    {
        $suspendedUser = User::factory()->create(['role' => 'company']);
        $suspendedCompany = $suspendedUser->company()->create(['status' => 'suspended']);
        $suspendedCompany->profile()->create(['name' => 'PT Suspended']);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->postJson('/api/teacher/partnerships', [
                'company_id' => $suspendedCompany->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // ---- List Partnerships ----

    public function test_teacher_can_list_own_school_partnerships(): void
    {
        $company1 = $this->createCompany('PT Alpha');
        $company2 = $this->createCompany('PT Beta');

        SchoolCompanyPartnership::create([
            'school_id' => $this->school->id,
            'company_id' => $company1->id,
            'status' => 'PENDING',
        ]);

        // Partnership from another school — should not appear
        $otherSchool = School::create(['name' => 'SMK N 2']);
        SchoolCompanyPartnership::create([
            'school_id' => $otherSchool->id,
            'company_id' => $company2->id,
            'status' => 'ACCEPTED',
        ]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.company.profile.name', 'PT Alpha');
    }

    public function test_teacher_can_filter_partnerships_by_status(): void
    {
        $company1 = $this->createCompany('PT Alpha');
        $company2 = $this->createCompany('PT Beta');

        SchoolCompanyPartnership::create([
            'school_id' => $this->school->id,
            'company_id' => $company1->id,
            'status' => 'PENDING',
        ]);
        SchoolCompanyPartnership::create([
            'school_id' => $this->school->id,
            'company_id' => $company2->id,
            'status' => 'ACCEPTED',
        ]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships?status=PENDING')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', 'PENDING');
    }

    // ---- View Partnership Detail ----

    public function test_teacher_can_view_own_school_partnership_detail(): void
    {
        $company = $this->createCompany();
        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $this->school->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
            'notes' => 'Kerja sama PKL',
        ]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson("/api/teacher/partnerships/{$partnership->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'PENDING')
            ->assertJsonPath('data.notes', 'Kerja sama PKL');
    }

    public function test_teacher_cannot_view_other_school_partnership(): void
    {
        $company = $this->createCompany();
        $otherSchool = School::create(['name' => 'SMK N 2']);
        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $otherSchool->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
        ]);

        $token = $this->teacherToken();

        $this->withToken($token)
            ->getJson("/api/teacher/partnerships/{$partnership->id}")
            ->assertStatus(403);
    }

    // ---- Auth & Role ----

    public function test_non_teacher_cannot_access_teacher_partnership_endpoints(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships')
            ->assertStatus(403);

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships/companies')
            ->assertStatus(403);

        $this->withToken($token)
            ->postJson('/api/teacher/partnerships', ['company_id' => 1])
            ->assertStatus(403);
    }

    public function test_teacher_partnership_endpoints_require_authentication(): void
    {
        $this->getJson('/api/teacher/partnerships')->assertStatus(401);
        $this->getJson('/api/teacher/partnerships/companies')->assertStatus(401);
        $this->postJson('/api/teacher/partnerships', ['company_id' => 1])->assertStatus(401);
    }

    public function test_teacher_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships')
            ->assertStatus(404);

        $this->withToken($token)
            ->getJson('/api/teacher/partnerships/companies')
            ->assertStatus(404);

        // POST: validation fires before controller check, so we get 422 (invalid company_id)
        // but it still passes role middleware, confirming teacher role is required.
        $this->withToken($token)
            ->postJson('/api/teacher/partnerships', ['company_id' => 9999])
            ->assertStatus(422);
    }
}
