<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentProfileRequest;
use App\Http\Requests\Student\UpdateStudentProfileRequest;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 8 — Student Profile.
 * Profil siswa (hanya profil milik sendiri, role student).
 */
class StudentProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/me/student — profil siswa yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat. Silakan lengkapi profil terlebih dahulu.', null, 404);
        }

        $student->load(['school', 'major', 'skills', 'portfolios', 'documents', 'certificates']);

        return $this->success(new StudentResource($student), 'Profil siswa berhasil diambil.');
    }

    /**
     * POST /api/me/student — buat profil siswa.
     */
    public function store(StoreStudentProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        // Jika skeleton sudah ada (dari registrasi), update.
        // Jika profil lengkap sudah ada, tolak.
        $student = $user->student;
        if ($student && $student->school_id) {
            return $this->error('Profil siswa sudah ada. Gunakan PUT untuk memperbarui.', null, 409);
        }

        if ($student) {
            // Update skeleton yang dibuat saat registrasi.
            $student->update($request->validated());
        } else {
            $student = $user->student()->create($request->validated());
        }

        $student->load(['school', 'major']);

        return $this->success(new StudentResource($student), 'Profil siswa berhasil dibuat.', 201);
    }

    /**
     * PUT /api/me/student — perbarui profil siswa.
     */
    public function update(UpdateStudentProfileRequest $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum ada. Gunakan POST untuk membuat profil.', null, 404);
        }

        $student->update($request->validated());
        $student->load(['school', 'major']);

        return $this->success(new StudentResource($student->fresh()), 'Profil siswa berhasil diperbarui.');
    }
}
