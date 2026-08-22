<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherProfileRequest;
use App\Http\Requests\Teacher\UpdateTeacherProfileRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 4 — School & Teacher.
 * Profil guru (hanya profil milik sendiri).
 */
class TeacherProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/me/teacher — profil guru yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat. Silakan lengkapi profil terlebih dahulu.', null, 404);
        }

        $teacher->load('school');

        return $this->success(new TeacherResource($teacher), 'Profil guru berhasil diambil.');
    }

    /**
     * POST /api/me/teacher — buat profil guru (guru memilih sekolahnya).
     */
    public function store(StoreTeacherProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        // Jika skeleton sudah ada (dari registrasi), update.
        // Jika profil lengkap sudah ada, tolak.
        $teacher = $user->teacher;
        if ($teacher && $teacher->school_id) {
            return $this->error('Profil guru sudah ada. Gunakan PUT untuk memperbarui.', null, 409);
        }

        if ($teacher) {
            // Update skeleton yang dibuat saat registrasi.
            $teacher->update($request->validated());
        } else {
            $teacher = $user->teacher()->create($request->validated());
        }

        $teacher->load('school');

        return $this->success(new TeacherResource($teacher), 'Profil guru berhasil dibuat.', 201);
    }

    /**
     * PUT /api/me/teacher — perbarui profil guru.
     */
    public function update(UpdateTeacherProfileRequest $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum ada. Gunakan POST untuk membuat profil.', null, 404);
        }

        $teacher->update($request->validated());
        $teacher->load('school');

        return $this->success(new TeacherResource($teacher), 'Profil guru berhasil diperbarui.');
    }
}
