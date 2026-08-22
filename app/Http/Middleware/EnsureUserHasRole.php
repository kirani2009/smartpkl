<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PHASE 2 — Role authorization middleware.
 *
 * Memastikan user yang terautentikasi memiliki salah satu role yang diizinkan.
 * Authorization tetap diperiksa di backend, bukan hanya frontend (docs/ai/SECURITY.json).
 *
 * Penggunaan: Route::middleware('role:admin')->...  atau  'role:teacher,admin'
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Belum login -> 401 (bukan 403).
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated: silakan login terlebih dahulu.',
                'data' => null,
                'errors' => null,
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Sudah login tapi role tidak berhak -> 403.
        if (! $user->hasRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Anda tidak memiliki akses ke resource ini.',
                'data' => null,
                'errors' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
