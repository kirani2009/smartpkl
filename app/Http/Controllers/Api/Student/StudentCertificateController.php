<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreCertificateRequest;
use App\Http\Requests\Student\UpdateCertificateRequest;
use App\Http\Resources\CertificateResource;
use App\Models\Certificate;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 8 — Student Profile.
 * CRUD sertifikat siswa.
 */
class StudentCertificateController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/me/student/certificates — daftar sertifikat.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $certificates = $student->certificates()->latest('issued_at')->get();

        return $this->success([
            'items' => CertificateResource::collection($certificates),
        ], 'Daftar sertifikat berhasil diambil.');
    }

    /**
     * POST /api/me/student/certificates — tambah sertifikat.
     */
    public function store(StoreCertificateRequest $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $certificate = $student->certificates()->create($request->validated());

        return $this->success(new CertificateResource($certificate), 'Sertifikat berhasil ditambahkan.', 201);
    }

    /**
     * GET /api/me/student/certificates/{certificate} — detail sertifikat.
     */
    public function show(Request $request, Certificate $certificate): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $certificate->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke sertifikat ini.', null, 403);
        }

        return $this->success(new CertificateResource($certificate), 'Detail sertifikat berhasil diambil.');
    }

    /**
     * PUT /api/me/student/certificates/{certificate} — perbarui sertifikat.
     */
    public function update(UpdateCertificateRequest $request, Certificate $certificate): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $certificate->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke sertifikat ini.', null, 403);
        }

        $certificate->update($request->validated());

        return $this->success(new CertificateResource($certificate->fresh()), 'Sertifikat berhasil diperbarui.');
    }

    /**
     * DELETE /api/me/student/certificates/{certificate} — hapus sertifikat.
     */
    public function destroy(Request $request, Certificate $certificate): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $certificate->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke sertifikat ini.', null, 403);
        }

        $certificate->delete();

        return $this->success(null, 'Sertifikat berhasil dihapus.');
    }
}
