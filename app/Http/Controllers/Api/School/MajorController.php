<?php

namespace App\Http\Controllers\Api\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\Major\StoreMajorRequest;
use App\Http\Requests\Major\UpdateMajorRequest;
use App\Http\Resources\MajorResource;
use App\Models\Major;
use App\Models\School;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * PHASE 4 — School & Teacher.
 * CRUD jurusan. Guru dari sekolah terkait atau admin yang boleh mengelola.
 */
class MajorController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/schools/{school}/majors — daftar jurusan sebuah sekolah.
     */
    public function index(School $school): JsonResponse
    {
        $schoolId = $school->id;
        $majors = \App\Models\Major::query()
            ->where('school_id', $schoolId)
            ->orWhereNull('school_id')
            ->withCount(['students' => function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            }])
            ->orderBy('name')
            ->get();

        return $this->success([
            'items' => MajorResource::collection($majors),
        ], 'Daftar jurusan berhasil diambil.');
    }

    /**
     * POST /api/schools/{school}/majors — tambah jurusan.
     */
    public function store(StoreMajorRequest $request, School $school): JsonResponse
    {
        $this->authorize('create', [Major::class, $school]);

        $major = $school->majors()->create($request->validated());

        return $this->success(new MajorResource($major), 'Jurusan berhasil ditambahkan.', 201);
    }

    /**
     * PUT /api/majors/{major} — perbarui jurusan.
     */
    public function update(UpdateMajorRequest $request, Major $major): JsonResponse
    {
        $this->authorize('update', $major);

        $major->update($request->validated());

        return $this->success(new MajorResource($major->fresh()), 'Jurusan berhasil diperbarui.');
    }

    /**
     * DELETE /api/majors/{major} — hapus jurusan.
     * Sebelum menghapus, lepaskan siswa dari jurusan ini (set major_id = NULL)
     * agar tidak melanggar foreign key constraint.
     */
    public function destroy(Major $major): JsonResponse
    {
        $this->authorize('delete', $major);

        // Lepaskan semua siswa dari jurusan ini (set major_id = NULL)
        // Data siswa tetap tersimpan, hanya relasi jurusan yang dilepas.
        \App\Models\Student::where('major_id', $major->id)
            ->update(['major_id' => null]);

        $major->delete();

        return $this->success(null, 'Jurusan berhasil dihapus.');
    }
}
