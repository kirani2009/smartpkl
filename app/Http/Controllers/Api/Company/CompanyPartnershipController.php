<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartnershipResource;
use App\Models\SchoolCompanyPartnership;
use App\Traits\ApiResponseTrait;
use App\Traits\HasCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 5 — Company.
 * Pengelolaan partnership dari sisi perusahaan.
 * Perusahaan mengajukan kerja sama ke sekolah.
 */
class CompanyPartnershipController extends Controller
{
    use ApiResponseTrait, HasCompany;

    /**
     * GET /api/company/partnerships — daftar partnership untuk perusahaan ini.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }

        $partnerships = SchoolCompanyPartnership::query()
            ->with(['school:id,name,city', 'requester:id,name'])
            ->where('company_id', $company->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => $partnerships->items(),
            'meta' => [
                'current_page' => $partnerships->currentPage(),
                'last_page' => $partnerships->lastPage(),
                'per_page' => $partnerships->perPage(),
                'total' => $partnerships->total(),
            ],
        ], 'Daftar partnership berhasil diambil.');
    }

    /**
     * GET /api/company/partnerships/{partnership} — detail partnership.
     */
    public function show(Request $request, SchoolCompanyPartnership $partnership): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $partnership->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke partnership ini.', null, 403);
        }

        $partnership->load(['school', 'requester']);

        return $this->success($partnership, 'Detail partnership berhasil diambil.');
    }

    /**
     * POST /api/company/partnerships — ajukan partnership ke sekolah.
     */
    public function store(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }

        $request->validate([
            'school_id' => ['required', 'exists:schools,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $schoolId = $request->input('school_id');

        // Cek apakah sudah ada partnership antara perusahaan dan sekolah ini
        $existing = SchoolCompanyPartnership::where('company_id', $company->id)
            ->where('school_id', $schoolId)
            ->first();

        if ($existing) {
            if ($existing->status === SchoolCompanyPartnership::STATUS_PENDING) {
                return $this->error('Pengajuan partnership ke sekolah ini sudah ada dan sedang menunggu jawaban.', null, 409);
            }
            if ($existing->status === SchoolCompanyPartnership::STATUS_ACCEPTED) {
                return $this->error('Sudah ada partnership aktif dengan sekolah ini.', null, 409);
            }
            // Re-submit if REJECTED or EXPIRED
            $existing->update([
                'status' => SchoolCompanyPartnership::STATUS_PENDING,
                'requested_by' => $request->user()->id,
                'responded_at' => null,
                'notes' => $request->input('notes'),
            ]);

            return $this->success(
                new PartnershipResource($existing->fresh(['school', 'requester'])),
                'Pengajuan partnership berhasil dikirim ulang.',
                201
            );
        }

        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $schoolId,
            'company_id' => $company->id,
            'requested_by' => $request->user()->id,
            'status' => SchoolCompanyPartnership::STATUS_PENDING,
            'notes' => $request->input('notes'),
        ]);

        return $this->success(
            new PartnershipResource($partnership->load(['school', 'requester'])),
            'Pengajuan partnership berhasil dikirim.',
            201
        );
    }

    /**
     * PUT /api/company/partnerships/{partnership}/accept — terima partnership.
     */
    public function accept(Request $request, SchoolCompanyPartnership $partnership): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $partnership->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke partnership ini.', null, 403);
        }

        if ($partnership->status !== SchoolCompanyPartnership::STATUS_PENDING) {
            return $this->error('Hanya partnership dengan status PENDING yang dapat diterima.', null, 422);
        }

        $partnership->update([
            'status' => SchoolCompanyPartnership::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        return $this->success($partnership->fresh(['school']), 'Partnership berhasil diterima.');
    }

    /**
     * PUT /api/company/partnerships/{partnership}/reject — tolak partnership.
     */
    public function reject(Request $request, SchoolCompanyPartnership $partnership): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $partnership->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke partnership ini.', null, 403);
        }

        if ($partnership->status !== SchoolCompanyPartnership::STATUS_PENDING) {
            return $this->error('Hanya partnership dengan status PENDING yang dapat ditolak.', null, 422);
        }

        $partnership->update([
            'status' => SchoolCompanyPartnership::STATUS_REJECTED,
            'responded_at' => now(),
        ]);

        return $this->success($partnership->fresh(['school']), 'Partnership berhasil ditolak.');
    }
}
