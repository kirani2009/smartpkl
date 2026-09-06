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
use Illuminate\Support\Facades\DB;
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
        $validated = $request->validated();

        // Hapus password_confirmation agar tidak lolos ke mass assignment
        unset($validated['password_confirmation']);

        try {
            // Cek email duplikat di dalam try/catch (bukan lewat rule 'unique'
            // pada validasi) supaya jika database sedang tidak tersedia, error
            // database ditangani dan dikembalikan sebagai pesan JSON yang jelas
            // — bukan exception mentah yang berakhir sebagai HTML/500 membingungkan.
            $exists = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($validated['email']))])->exists();

            if ($exists) {
                return $this->error('Email sudah terdaftar.', [
                    'email' => ['Email sudah terdaftar.'],
                ], 422);
            }

            // User + profil skeleton dibuat dalam satu transaksi agar tidak ada
            // user tanpa profil tertinggal saat pembuatan profil gagal.
            $user = DB::transaction(function () use ($validated) {
                $user = User::create($validated);

                match ($user->role) {
                    'teacher' => $user->teacher()->create([]),
                    'student' => $user->student()->create([]),
                    'company' => Company::create([
                        'user_id' => $user->id,
                        'status' => Company::STATUS_ACTIVE,
                    ]),
                    default => null,
                };

                return $user;
            });
        } catch (QueryException $e) {
            // Duplicate entry (email sama terdaftar di saat bersamaan) tetap
            // dilaporkan sebagai email sudah terdaftar, bukan error database.
            if (isset($e->errorInfo[1]) && (int) $e->errorInfo[1] === 1062) {
                return $this->error('Email sudah terdaftar.', [
                    'email' => ['Email sudah terdaftar.'],
                ], 422);
            }

            Log::error('Register DB error: ' . $e->getMessage());
            return $this->error('Gagal membuat akun: masalah database.', null, 500);
        } catch (Exception $e) {
            Log::error('Register error: ' . $e->getMessage());
            return $this->error('Gagal membuat akun: ' . $e->getMessage(), null, 500);
        }

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
     *
     * Error koneksi/skema database ditangani terpisah dari kredensial salah,
     * sehingga gangguan database sesaat tidak salah dilaporkan sebagai
     * "kredensial tidak valid".
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $email = trim($request->validated('email'));

        try {
            // Pencocokan email case-insensitive (tidak bergantung pada huruf besar/kecil).
            $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
        } catch (QueryException $e) {
            Log::error('Login DB error: ' . $e->getMessage());
            return $this->error('Gagal login: database sedang tidak tersedia. Silakan coba lagi.', null, 503);
        } catch (Exception $e) {
            Log::error('Login DB error: ' . $e->getMessage());
            return $this->error('Gagal login: masalah database.', null, 500);
        }

        if (! $user) {
            return $this->error('Kredensial tidak valid.', null, 401);
        }

        try {
            $passwordOk = Hash::check($request->validated('password'), $user->password);
        } catch (Exception $e) {
            Log::error('Login hash error: ' . $e->getMessage());
            return $this->error('Gagal memverifikasi password. Silakan hubungi admin.', null, 500);
        }

        if (! $passwordOk) {
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
