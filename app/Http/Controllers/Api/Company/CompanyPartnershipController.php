<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartnershipResource;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Traits\ApiResponseTrait;
use App\Traits\HasCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 5 — Company.
 * Pengelolaan partnership dari sisi perusahaan.
 * Perusahaan mengajukan kerja sama. Guru yang akan menerima atau menolak.
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
     * POST /api/company/partnerships — ajukan partnership.
     * Sekolah otomatis ditentukan (single school app).
     */
    public function store(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }

        $request->validate([
            'notes' => ['required', 'string', 'max:1000'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'teacher_name' => ['nullable', 'string', 'max:255'],
        ]);

        // Resolve school from school_name or default to first school
        $school = null;
        $schoolName = $request->input('school_name');
        if (! empty($schoolName)) {
            $school = School::where('name', 'like', '%' . trim($schoolName) . '%')->first();
        }
        if (! $school) {
            $school = School::first();
        }

        if (! $school) {
            return $this->error('Tidak ada sekolah yang tersedia di sistem.', null, 422);
        }

        // Resolve teacher (requested_by) from teacher_name if provided
        $requestedBy = $request->user()->id;
        $teacherName = $request->input('teacher_name');
        if (! empty($teacherName)) {
            $teacherUser = \App\Models\User::where('role', 'teacher')
                ->where('name', 'like', '%' . trim($teacherName) . '%')
                ->first();
            if ($teacherUser) {
                $requestedBy = $teacherUser->id;
            }
        }

        $schoolId = $school->id;

        // Cek apakah sudah ada partnership antara perusahaan dan sekolah ini
        $existing = SchoolCompanyPartnership::where('company_id', $company->id)
            ->where('school_id', $schoolId)
            ->first();

        if ($existing) {
            if ($existing->status === SchoolCompanyPartnership::STATUS_PENDING) {
                return $this->error('Pengajuan partnership sudah ada dan sedang menunggu persetujuan guru.', null, 409);
            }
            if ($existing->status === SchoolCompanyPartnership::STATUS_ACCEPTED) {
                return $this->error('Sudah ada partnership aktif dengan sekolah ini.', null, 409);
            }
            // Re-submit if REJECTED or EXPIRED
            $existing->update([
                'status' => SchoolCompanyPartnership::STATUS_PENDING,
                'requested_by' => $requestedBy,
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
            'requested_by' => $requestedBy,
            'status' => SchoolCompanyPartnership::STATUS_PENDING,
            'notes' => $request->input('notes'),
        ]);

        return $this->success(
            new PartnershipResource($partnership->load(['school', 'requester'])),
            'Pengajuan partnership berhasil dikirim. Menunggu persetujuan guru.',
            201
        );
    }

    /**
     * DELETE /api/company/partnerships/{partnership} — batalkan pengajuan partnership.
     * Hanya bisa dibatalkan jika status masih PENDING.
     */
    public function cancel(Request $request, SchoolCompanyPartnership $partnership): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $partnership->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke partnership ini.', null, 403);
        }

        if ($partnership->status !== SchoolCompanyPartnership::STATUS_PENDING) {
            return $this->error('Hanya pengajuan dengan status PENDING yang dapat dibatalkan.', null, 422);
        }

        $partnership->delete();

        return $this->success(null, 'Pengajuan partnership berhasil dibatalkan.');
    }
}
