<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentProfileRequest;
use App\Http\Requests\Student\UpdateStudentProfileRequest;
use App\Http\Resources\StudentResource;
use App\Models\Major;
use App\Models\School;
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

        $validated = $request->validated();

        // Auto-match school_id from school_name if not provided
        if (empty($validated['school_id']) && ! empty($validated['school_name'])) {
            $school = School::where('name', $validated['school_name'])->first();
            if ($school) {
                $validated['school_id'] = $school->id;
            }
        }

        // Auto-match major_id from major_name if not provided
        if (empty($validated['major_id']) && ! empty($validated['major_name'])) {
            $major = Major::where('name', $validated['major_name'])->first();
            if ($major) {
                $validated['major_id'] = $major->id;
            }
        }

        // Jika skeleton sudah ada (dari registrasi), update.
        // Jika profil lengkap sudah ada, tolak.
        $student = $user->student;
        if ($student && $student->school_id) {
            return $this->error('Profil siswa sudah ada. Gunakan PUT untuk memperbarui.', null, 409);
        }

        if ($student) {
            // Update skeleton yang dibuat saat registrasi.
            $student->update($validated);
        } else {
            $student = $user->student()->create($validated);
        }

        // Sync name to users table for backward compatibility
        if (! empty($validated['name'])) {
            $user->update(['name' => $validated['name']]);
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

        $validated = $request->validated();

        // Auto-match school_id from school_name if school_name changed
        if (! empty($validated['school_name']) && empty($validated['school_id'])) {
            $school = School::where('name', $validated['school_name'])->first();
            $validated['school_id'] = $school?->id;
        }

        // Auto-match major_id from major_name if major_name changed
        if (! empty($validated['major_name']) && empty($validated['major_id'])) {
            $major = Major::where('name', $validated['major_name'])->first();
            $validated['major_id'] = $major?->id;
        }

        $student->update($validated);

        // Sync name to users table for backward compatibility
        if (! empty($validated['name'])) {
            $student->user->update(['name' => $validated['name']]);
        }

        $student->load(['school', 'major']);

        return $this->success(new StudentResource($student->fresh()), 'Profil siswa berhasil diperbarui.');
    }
}
