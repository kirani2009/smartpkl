<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: mendaftarkan route uji yang dilindungi middleware role.
     */
    private function registerProtectedRoute(string $route, array $roles): void
    {
        Route::middleware(['auth:sanctum', 'role:' . implode(',', $roles)])
            ->get($route, fn () => response()->json(['success' => true, 'message' => 'Akses diizinkan']));
    }

    public function test_admin_can_access_admin_only_route(): void
    {
        $this->registerProtectedRoute('/api/_test/admin-only', ['admin']);

        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/_test/admin-only')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_student_cannot_access_admin_only_route(): void
    {
        $this->registerProtectedRoute('/api/_test/admin-only', ['admin']);

        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/_test/admin-only')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_teacher_cannot_access_company_route(): void
    {
        $this->registerProtectedRoute('/api/_test/company-only', ['company']);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $token = $teacher->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/_test/company-only')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_role_middleware_allows_multiple_roles(): void
    {
        $this->registerProtectedRoute('/api/_test/teacher-or-company', ['teacher', 'company']);

        foreach (['teacher', 'company'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $token = $user->createToken('auth-token')->plainTextToken;

            $this->withToken($token)
                ->getJson('/api/_test/teacher-or-company')
                ->assertStatus(200);
        }
    }

    public function test_role_middleware_rejects_unauthenticated_request(): void
    {
        $this->registerProtectedRoute('/api/_test/admin-only', ['admin']);

        $this->getJson('/api/_test/admin-only')->assertStatus(401);
    }
}
