<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\SchoolCompanyPartnership;
use App\Traits\ApiResponseTrait;
use App\Traits\HasCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 5 — Company.
 * Pengelolaan partnership dari sisi perusahaan.
 * Company melihat partnership masuk dan bisa accept/reject.
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
