<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 5 — Company.
 * Pengelolaan partnership dari sisi perusahaan.
 */
class CompanyPartnershipTest extends TestCase
{
    use RefreshDatabase;

    private function companyWithProfile(): User
    {
        $user = User::factory()->create(['role' => 'company']);
        $company = $user->company()->create(['status' => 'active']);
        $company->profile()->create(['name' => 'PT Test Company']);
        return $user;
    }

    public function test_company_can_list_partnerships(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        $school = School::create(['name' => 'SMK N 1']);
        SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($token)
            ->getJson('/api/company/partnerships')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.school.name', 'SMK N 1');
    }

    public function test_company_can_filter_partnerships_by_status(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        // Different schools to avoid unique constraint on (school_id, company_id)
        $school1 = School::create(['name' => 'SMK N 1']);
        $school2 = School::create(['name' => 'SMK N 2']);
        SchoolCompanyPartnership::create([
            'school_id' => $school1->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
        ]);
        SchoolCompanyPartnership::create([
            'school_id' => $school2->id,
            'company_id' => $company->id,
            'status' => 'ACCEPTED',
        ]);

        $this->withToken($token)
            ->getJson('/api/company/partnerships?status=PENDING')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', 'PENDING');
    }

    public function test_company_can_view_partnership_detail(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        $school = School::create(['name' => 'SMK N 1']);
        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($token)
            ->getJson("/api/company/partnerships/{$partnership->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.school.name', 'SMK N 1')
            ->assertJsonPath('data.status', 'PENDING');
    }

    public function test_company_can_accept_pending_partnership(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        $school = School::create(['name' => 'SMK N 1']);
        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($token)
            ->putJson("/api/company/partnerships/{$partnership->id}/accept")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'ACCEPTED');

        $this->assertDatabaseHas('school_company_partnerships', [
            'id' => $partnership->id,
            'status' => 'ACCEPTED',
        ]);
    }

    public function test_company_can_reject_pending_partnership(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        $school = School::create(['name' => 'SMK N 1']);
        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($token)
            ->putJson("/api/company/partnerships/{$partnership->id}/reject")
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'REJECTED');

        $this->assertDatabaseHas('school_company_partnerships', [
            'id' => $partnership->id,
            'status' => 'REJECTED',
        ]);
    }

    public function test_company_cannot_accept_already_accepted_partnership(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        $school = School::create(['name' => 'SMK N 1']);
        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'status' => 'ACCEPTED',
        ]);

        $this->withToken($token)
            ->putJson("/api/company/partnerships/{$partnership->id}/accept")
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_company_cannot_accept_other_companies_partnership(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;

        $school = School::create(['name' => 'SMK N 1']);
        $otherCompany = User::factory()->create(['role' => 'company']);
        $otherCompanyObj = $otherCompany->company()->create(['status' => 'active']);

        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $otherCompanyObj->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($token)
            ->putJson("/api/company/partnerships/{$partnership->id}/accept")
            ->assertStatus(403);
    }

    public function test_company_cannot_access_other_companies_partnerships(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;

        $school = School::create(['name' => 'SMK N 1']);
        $otherCompany = User::factory()->create(['role' => 'company']);
        $otherCompanyObj = $otherCompany->company()->create(['status' => 'active']);

        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $otherCompanyObj->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($token)
            ->getJson("/api/company/partnerships/{$partnership->id}")
            ->assertStatus(403);
    }

    public function test_non_company_cannot_access_company_partnerships(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/company/partnerships')
            ->assertStatus(403);
    }

    public function test_partnership_endpoints_require_authentication(): void
    {
        $this->getJson('/api/company/partnerships')->assertStatus(401);
    }

    public function test_company_only_sees_own_partnerships(): void
    {
        $user = $this->companyWithProfile();
        $token = $user->createToken('auth-token')->plainTextToken;
        $company = $user->company;

        $school = School::create(['name' => 'SMK N 1']);

        // Own partnership
        SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'status' => 'PENDING',
        ]);

        // Other company's partnership
        $otherCompany = User::factory()->create(['role' => 'company']);
        $otherCompanyObj = $otherCompany->company()->create(['status' => 'active']);
        SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $otherCompanyObj->id,
            'status' => 'PENDING',
        ]);

        $this->withToken($token)
            ->getJson('/api/company/partnerships')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }
}
