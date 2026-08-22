<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        $user = User::create($request->validated());

        $token = $user->createToken('auth-token')->plainTextToken;

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
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return $this->error('Kredensial tidak valid.', null, 401);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

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
