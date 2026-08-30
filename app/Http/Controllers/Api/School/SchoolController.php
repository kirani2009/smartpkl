<?php

namespace App\Http\Controllers\Api\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\StoreSchoolRequest;
use App\Http\Requests\School\UpdateSchoolRequest;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 4 — School & Teacher.
 * CRUD sekolah. Create/update/delete hanya admin (ROLES.json).
 */
class SchoolController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/schools — daftar sekolah (paginasi + pencarian).
     */
    public function index(Request $request): JsonResponse
    {
        $schools = School::query()
            ->withCount(['majors', 'teachers', 'students'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim($request->input('q'));
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            })
            ->when($request->filled('city'), fn ($query) => $query->where('city', $request->input('city')))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => SchoolResource::collection($schools),
            'meta' => [
                'current_page' => $schools->currentPage(),
                'last_page' => $schools->lastPage(),
                'per_page' => $schools->perPage(),
                'total' => $schools->total(),
            ],
        ], 'Daftar sekolah berhasil diambil.');
    }

    /**
     * GET /api/schools/{school} — detail sekolah.
     */
    public function show(School $school): JsonResponse
    {
        $school->loadCount(['majors', 'teachers', 'students']);

        return $this->success(new SchoolResource($school), 'Detail sekolah berhasil diambil.');
    }

    /**
     * POST /api/schools — buat sekolah (admin).
     */
    public function store(StoreSchoolRequest $request): JsonResponse
    {
        $school = School::create($request->validated());

        return $this->success(new SchoolResource($school), 'Sekolah berhasil dibuat.', 201);
    }

    /**
     * PUT /api/schools/{school} — perbarui sekolah (admin).
     */
    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        $school->update($request->validated());

        return $this->success(new SchoolResource($school->fresh()), 'Sekolah berhasil diperbarui.');
    }

    /**
     * DELETE /api/schools/{school} — hapus sekolah (admin).
     */
    public function destroy(School $school): JsonResponse
    {
        $school->delete();

        return $this->success(null, 'Sekolah berhasil dihapus.');
    }

    /**
     * POST /api/schools/resolve — cari atau buat sekolah berdasarkan nama.
     */
    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $name = trim($request->input('name'));
        $school = School::where('name', $name)->first();

        if (! $school) {
            $school = School::create(['name' => $name]);
        }

        return $this->success(new SchoolResource($school), 'Sekolah berhasil ditemukan/dibuat.');
    }
}
