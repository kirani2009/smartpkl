<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ---- Register ----

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Budi Siswa',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'student')
            ->assertJsonPath('data.user.email', 'budi@example.com')
            ->assertJsonStructure([
                'data' => ['token', 'token_type', 'user' => ['id', 'name', 'email', 'role']],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role' => 'student',
        ]);
    }

    public function test_registration_hashes_password(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Siti Siswa',
            'email' => 'siti@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
        ])->assertStatus(201);

        $user = User::where('email', 'siti@example.com')->firstOrFail();

        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_registration_rejects_invalid_role(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Ani',
            'email' => 'ani@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ])->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('role');
    }

    public function test_registration_rejects_admin_role(): void
    {
        // Anti privilege escalation: admin tidak boleh mendaftar sendiri.
        $this->postJson('/api/auth/register', [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_registration_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'same@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Dewi',
            'email' => 'same@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_requires_minimum_password_length(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Eka',
            'email' => 'eka@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => 'student',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    // ---- Login ----

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
            'role' => 'teacher',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'teacher')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'wrong@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401)
            ->assertJsonPath('success', false);
    }

    // ---- Logout ----

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Simulasikan request baru (guard sanctum di-cache per request di test),
        // lalu pastikan token yang sudah dicabut tidak lagi valid.
        auth()->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertStatus(401);
    }

    // ---- Current user ----

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Fajar Guru',
            'role' => 'teacher',
        ]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Fajar Guru')
            ->assertJsonPath('data.role', 'teacher')
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }
}
