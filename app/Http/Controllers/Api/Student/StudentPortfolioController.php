<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StorePortfolioRequest;
use App\Http\Requests\Student\UpdatePortfolioRequest;
use App\Http\Resources\PortfolioResource;
use App\Models\Portfolio;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 8 — Student Profile.
 * CRUD portofolio siswa.
 */
class StudentPortfolioController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/me/student/portfolios — daftar portofolio.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $portfolios = $student->portfolios()->latest()->get();

        return $this->success([
            'items' => PortfolioResource::collection($portfolios),
        ], 'Daftar portofolio berhasil diambil.');
    }

    /**
     * POST /api/me/student/portfolios — buat portofolio baru.
     */
    public function store(StorePortfolioRequest $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $portfolio = $student->portfolios()->create($request->validated());

        return $this->success(new PortfolioResource($portfolio), 'Portofolio berhasil dibuat.', 201);
    }

    /**
     * GET /api/me/student/portfolios/{portfolio} — detail portofolio.
     */
    public function show(Request $request, Portfolio $portfolio): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $portfolio->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke portofolio ini.', null, 403);
        }

        return $this->success(new PortfolioResource($portfolio), 'Detail portofolio berhasil diambil.');
    }

    /**
     * PUT /api/me/student/portfolios/{portfolio} — perbarui portofolio.
     */
    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $portfolio->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke portofolio ini.', null, 403);
        }

        $portfolio->update($request->validated());

        return $this->success(new PortfolioResource($portfolio->fresh()), 'Portofolio berhasil diperbarui.');
    }

    /**
     * DELETE /api/me/student/portfolios/{portfolio} — hapus portofolio.
     */
    public function destroy(Request $request, Portfolio $portfolio): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $portfolio->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke portofolio ini.', null, 403);
        }

        $portfolio->delete();

        return $this->success(null, 'Portofolio berhasil dihapus.');
    }
}
