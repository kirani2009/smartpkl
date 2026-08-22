<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 5 — Company.
 * Profil perusahaan (profil sendiri, role company).
 */
class CompanyProfileTest extends TestCase
{
    use RefreshDatabase;

    private function companyToken(): string
    {
        return User::factory()->create(['role' => 'company'])
            ->createToken('auth-token')->plainTextToken;
    }

    public function test_company_can_create_own_profile(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/me/company', [
                'name' => 'PT Tech Indonesia',
                'industry' => 'Technology',
                'address' => 'Jl. Sudirman No. 100',
                'city' => 'Jakarta',
                'phone' => '0211234567',
                'email' => 'info@techindonesia.com',
                'website' => 'https://techindonesia.com',
                'description' => 'Perusahaan teknologi terkemuka',
                'established_year' => 2010,
                'employee_count' => 500,
            ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.profile.name', 'PT Tech Indonesia')
            ->assertJsonPath('data.profile.industry', 'Technology')
            ->assertJsonPath('data.profile.city', 'Jakarta')
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('company_profiles', ['name' => 'PT Tech Indonesia']);
    }

    public function test_company_can_view_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $company = $user->company()->create(['status' => 'active']);
        $company->profile()->create([
            'name' => 'PT Maju Jaya',
            'industry' => 'Manufacturing',
            'city' => 'Bandung',
        ]);

        $this->withToken($token)
            ->getJson('/api/me/company')
            ->assertStatus(200)
            ->assertJsonPath('data.profile.name', 'PT Maju Jaya')
            ->assertJsonPath('data.profile.city', 'Bandung');
    }

    public function test_company_can_update_own_profile(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $company = $user->company()->create(['status' => 'active']);
        $company->profile()->create([
            'name' => 'PT Lama',
            'industry' => 'Old Industry',
        ]);

        $this->withToken($token)
            ->putJson('/api/me/company', [
                'name' => 'PT Baru',
                'industry' => 'New Industry',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.profile.name', 'PT Baru')
            ->assertJsonPath('data.profile.industry', 'New Industry');
    }

    public function test_duplicate_profile_creation_returns_409(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $user->company()->create(['status' => 'active']);

        $this->withToken($token)
            ->postJson('/api/me/company', ['name' => 'Duplicate'])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_profile_creation_requires_name(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->postJson('/api/me/company', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_get_profile_without_profile_returns_404(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->getJson('/api/me/company')
            ->assertStatus(404);
    }

    public function test_update_without_profile_returns_404(): void
    {
        $token = $this->companyToken();

        $this->withToken($token)
            ->putJson('/api/me/company', ['name' => 'X'])
            ->assertStatus(404);
    }

    public function test_non_company_cannot_access_company_profile(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/me/company', ['name' => 'Test'])
            ->assertStatus(403);

        $this->withToken($token)
            ->getJson('/api/me/company')
            ->assertStatus(403);
    }

    public function test_company_profile_endpoints_require_authentication(): void
    {
        $this->getJson('/api/me/company')->assertStatus(401);
        $this->postJson('/api/me/company', ['name' => 'Test'])->assertStatus(401);
        $this->putJson('/api/me/company', ['name' => 'Test'])->assertStatus(401);
    }

    public function test_profile_update_with_partial_data(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $company = $user->company()->create(['status' => 'active']);
        $company->profile()->create([
            'name' => 'PT Original',
            'industry' => 'Original',
            'city' => 'Jakarta',
        ]);

        // Update only name, other fields should remain
        $this->withToken($token)
            ->putJson('/api/me/company', ['name' => 'PT Updated'])
            ->assertStatus(200)
            ->assertJsonPath('data.profile.name', 'PT Updated')
            ->assertJsonPath('data.profile.city', 'Jakarta'); // unchanged
    }
}
