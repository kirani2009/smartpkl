<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Simulate the exact frontend flow:
     * 1. Register company user
     * 2. Login
     * 3. Create company profile via POST /api/me/company
     * 4. Verify token still works after profile creation
     */
    public function test_company_can_create_profile_and_stay_authenticated(): void
    {
        // Step 1: Register
        $response = $this->postJson('/api/auth/register', [
            'name' => 'PT Test Corp',
            'email' => 'testcorp@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'company',
        ]);
        $response->assertStatus(201);
        $token = $response->json('data.token');

        // Step 2: Verify token works (GET /api/me)
        $this->withToken($token)
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.role', 'company');

        // Step 3: Verify GET /api/me/company returns 200 (skeleton company auto-created)
        // Profile belum diisi, tapi company row sudah ada.
        $this->withToken($token)
            ->getJson('/api/me/company')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Step 4: Create company profile
        $storeResponse = $this->withToken($token)
            ->postJson('/api/me/company', [
                'name' => 'PT Test Corp',
                'industry' => 'Technology',
                'address' => 'Jl. Test No. 1',
                'city' => 'Jakarta',
                'phone' => '021-1234567',
                'email' => 'info@testcorp.com',
                'website' => 'https://testcorp.com',
                'description' => 'Test company',
                'established_year' => 2020,
                'employee_count' => 50,
            ]);

        $storeResponse->assertStatus(201)
            ->assertJsonPath('success', true);

        // Step 5: Verify token STILL works after profile creation
        $meResponse = $this->withToken($token)
            ->getJson('/api/me');
        $meResponse->assertStatus(200);

        // Step 6: Verify GET /api/me/company now returns 200
        $companyResponse = $this->withToken($token)
            ->getJson('/api/me/company');
        $companyResponse->assertStatus(200)
            ->assertJsonPath('data.profile.name', 'PT Test Corp');

        // Step 7: Verify GET /api/company/dashboard works
        $dashboardResponse = $this->withToken($token)
            ->getJson('/api/company/dashboard');
        $dashboardResponse->assertStatus(200);
    }

    /**
     * Test that unauthenticated user cannot create profile
     */
    public function test_unauthenticated_user_cannot_create_profile(): void
    {
        $this->postJson('/api/me/company', [
            'name' => 'Test',
        ])->assertStatus(401);
    }

    /**
     * Test that non-company user cannot create profile
     */
    public function test_teacher_cannot_create_company_profile(): void
    {
        $school = School::create(['name' => 'SMK Test']);
        $user = User::factory()->create(['role' => 'teacher']);
        $user->teacher()->create(['school_id' => $school->id]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/me/company', ['name' => 'Test'])
            ->assertStatus(403);
    }

    /**
     * Test validation errors don't cause 401
     */
    public function test_validation_error_returns_422_not_401(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $token = $user->createToken('auth-token')->plainTextToken;

        // Missing required 'name' field
        $response = $this->withToken($token)
            ->postJson('/api/me/company', [
                'industry' => 'Tech',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('name');

        // Verify token still works
        $this->withToken($token)
            ->getJson('/api/me')
            ->assertStatus(200);
    }

    /**
     * Test that creating profile doesn't invalidate the token
     * by checking token exists in database after profile creation
     */
    public function test_token_persists_after_profile_creation(): void
    {
        $user = User::factory()->create(['role' => 'company']);
        $token = $user->createToken('auth-token')->plainTextToken;

        // Count tokens before
        $tokensBefore = $user->tokens()->count();

        // Create profile
        $this->withToken($token)
            ->postJson('/api/me/company', [
                'name' => 'Test Corp',
            ])
            ->assertStatus(201);

        // Count tokens after — should be the same
        $tokensAfter = $user->fresh()->tokens()->count();
        $this->assertEquals($tokensBefore, $tokensAfter);
    }
}
