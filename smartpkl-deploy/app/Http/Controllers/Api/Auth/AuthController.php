<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Exception;

/**
 * PHASE 2 — Authentication (docs/ai/AUTH.json).
 *
 * Register, Login, Logout, dan Current User.
 * Token dikelola dengan Laravel Sanctum (Bearer token).
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * POST /api/auth/register
     * Mendaftarkan user baru dan mengembalikan token akses.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Hapus password_confirmation agar tidak lolos ke mass assignment
            unset($validated['password_confirmation']);

            $user = User::create($validated);
        } catch (QueryException $e) {
            Log::error('Register DB error: ' . $e->getMessage());
            return $this->error('Gagal membuat akun: masalah database.', null, 500);
        } catch (Exception $e) {
            Log::error('Register error: ' . $e->getMessage());
            return $this->error('Gagal membuat akun: ' . $e->getMessage(), null, 500);
        }

        // Auto-create skeleton profile berdasarkan role.
        match ($user->role) {
            'teacher' => $user->teacher()->create([]),
            'student' => $user->student()->create([]),
            'company' => Company::create([
                'user_id' => $user->id,
                'status' => Company::STATUS_ACTIVE,
            ]),
            default => null,
        };

        try {
            $token = $user->createToken('auth-token')->plainTextToken;
        } catch (Exception $e) {
            Log::error('Register token error: ' . $e->getMessage());
            // Tetap return sukses meskipun token gagal — user sudah terdaftar
            return $this->success([
                'token' => null,
                'token_type' => 'Bearer',
                'user' => new UserResource($user),
            ], 'Registrasi berhasil. Silakan login.', 201);
        }

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Registrasi berhasil.', 201);
    }

    /**
     * POST /api/auth/login
     * Login dan mengembalikan token akses baru.
     *
     * Kredensial diverifikasi langsung dengan Hash::check (independen dari
     * guard default) agar aman dipakai bersama Sanctum token guard.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->validated('email'))->first();
        } catch (Exception $e) {
            Log::error('Login DB error: ' . $e->getMessage());
            return $this->error('Gagal login: masalah database.', null, 500);
        }

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return $this->error('Kredensial tidak valid.', null, 401);
        }

        try {
            $token = $user->createToken('auth-token')->plainTextToken;
        } catch (Exception $e) {
            Log::error('Login token error: ' . $e->getMessage());
            return $this->error('Gagal membuat token autentikasi. Silakan hubungi admin.', null, 500);
        }

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 'Login berhasil.');
    }

    /**
     * POST /api/auth/logout
     * Menghapus token akses saat ini (diperlukan autentikasi).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout berhasil.');
    }

    /**
     * GET /api/me
     * Mengembalikan data user yang sedang login.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()), 'Data user berhasil diambil.');
    }
}
