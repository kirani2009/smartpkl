<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function actingUser(): array
    {
        $user = User::factory()->create([
            'email' => 'profile@example.com',
            'password' => Hash::make('password123'),
        ]);

        return [$user, $user->createToken('auth-token')->plainTextToken];
    }

    public function test_user_can_update_name_and_email(): void
    {
        [$user, $token] = $this->actingUser();

        $this->withToken($token)
            ->putJson('/api/me', [
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Nama Baru')
            ->assertJsonPath('data.email', 'baru@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ]);
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        [$user, $token] = $this->actingUser();

        $this->withToken($token)
            ->putJson('/api/me', [
                'current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ])
            ->assertStatus(200);

        $this->assertTrue(Hash::check('newpassword456', $user->fresh()->password));

        // Login dengan password baru berhasil.
        $this->postJson('/api/auth/login', [
            'email' => 'profile@example.com',
            'password' => 'newpassword456',
        ])->assertStatus(200);
    }

    public function test_changing_password_requires_current_password(): void
    {
        [$user, $token] = $this->actingUser();

        $this->withToken($token)
            ->putJson('/api/me', [
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_changing_password_rejects_wrong_current_password(): void
    {
        [$user, $token] = $this->actingUser();

        $this->withToken($token)
            ->putJson('/api/me', [
                'current_password' => 'salah-password',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');
    }

    public function test_user_cannot_use_email_of_another_user(): void
    {
        User::factory()->create(['email' => 'dipakai@example.com']);
        [$user, $token] = $this->actingUser();

        $this->withToken($token)
            ->putJson('/api/me', ['email' => 'dipakai@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_profile_update_requires_authentication(): void
    {
        $this->putJson('/api/me', ['name' => 'Tanpa Login'])->assertStatus(401);
    }
}
