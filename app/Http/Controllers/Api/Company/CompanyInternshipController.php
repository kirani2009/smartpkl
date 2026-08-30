<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Internship\StoreInternshipRequest;
use App\Http\Requests\Internship\UpdateInternshipRequest;
use App\Http\Resources\InternshipResource;
use App\Models\InternshipListing;
use App\Traits\ApiResponseTrait;
use App\Traits\HasCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 7 — Internship.
 * Company mengelola lowongan PKL: buat, lihat, update, hapus.
 */
class CompanyInternshipController extends Controller
{
    use ApiResponseTrait, HasCompany;

    /**
     * GET /api/company/internships — daftar lowongan milik perusahaan ini.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }

        $internships = InternshipListing::query()
            ->with(['major:id,name', 'requirements'])
            ->where('company_id', $company->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim($request->input('q'));
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%");
            })
            ->withCount('applications')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => InternshipResource::collection($internships),
            'meta' => [
                'current_page' => $internships->currentPage(),
                'last_page' => $internships->lastPage(),
                'per_page' => $internships->perPage(),
                'total' => $internships->total(),
            ],
        ], 'Daftar lowongan berhasil diambil.');
    }

    /**
     * GET /api/company/internships/{internship} — detail lowongan.
     */
    public function show(Request $request, InternshipListing $internship): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $internship->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke lowongan ini.', null, 403);
        }

        $internship->load(['major', 'requirements']);
        $internship->loadCount('applications');

        return $this->success(new InternshipResource($internship), 'Detail lowongan berhasil diambil.');
    }

    /**
     * POST /api/company/internships — buat lowongan baru.
     * Lowongan langsung berstatus PUBLISHED.
     */
    public function store(StoreInternshipRequest $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat. Silakan lengkapi profil terlebih dahulu.', null, 404);
        }

        try {
            $internship = DB::transaction(function () use ($request, $company) {
                $validated = $request->validated();
                $requirements = $validated['requirements'] ?? [];

                unset($validated['requirements']);

                $validated['company_id'] = $company->id;
                $validated['status'] = InternshipListing::STATUS_PUBLISHED;

                // Auto-set title from company profile name if not provided
                if (empty($validated['title'])) {
                    $validated['title'] = $company->profile?->name ?? 'Lowongan PKL';
                }

                $internship = InternshipListing::create($validated);

                // Simpan requirements
                foreach ($requirements as $description) {
                    $internship->requirements()->create(['description' => $description]);
                }

                return $internship;
            });

            $internship->load(['major', 'requirements']);

            return $this->success(new InternshipResource($internship), 'Lowongan berhasil dibuat dan langsung dipublikasikan.', 201);
        } catch (\Exception $e) {
            return $this->error('Gagal menyimpan lowongan: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * PUT /api/company/internships/{internship} — perbarui lowongan.
     */
    public function update(UpdateInternshipRequest $request, InternshipListing $internship): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $internship->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke lowongan ini.', null, 403);
        }

        DB::transaction(function () use ($request, $internship) {
            $validated = $request->validated();
            $requirements = $validated['requirements'] ?? null;

            unset($validated['requirements']);

            $internship->update($validated);

            // Update requirements jika dikirim
            if ($requirements !== null) {
                $internship->requirements()->delete();
                foreach ($requirements as $description) {
                    $internship->requirements()->create(['description' => $description]);
                }
            }
        });

        $internship->load(['major', 'requirements']);

        return $this->success(new InternshipResource($internship->fresh()), 'Lowongan berhasil diperbarui.');
    }

    /**
     * DELETE /api/company/internships/{internship} — hapus lowongan.
     */
    public function destroy(Request $request, InternshipListing $internship): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $internship->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke lowongan ini.', null, 403);
        }

        // Cek apakah ada lamaran masuk
        $hasApplications = $internship->applications()->exists();
        if ($hasApplications) {
            return $this->error('Lowongan tidak bisa dihapus karena sudah ada lamaran. Gunakan status CLOSED.', null, 422);
        }

        DB::transaction(function () use ($internship) {
            $internship->requirements()->delete();
            $internship->delete();
        });

        return $this->success(null, 'Lowongan berhasil dihapus.');
    }
}
