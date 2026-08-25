<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partnership\StorePartnershipRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\PartnershipResource;
use App\Models\Company;
use App\Models\SchoolCompanyPartnership;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 6 — Partnership.
 * Guru mengelola partnership: mencari perusahaan, mengirim request, melihat daftar partnership.
 */
class TeacherPartnershipController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/teacher/partnerships/companies — cari perusahaan untuk diajak kerja sama.
     * Menampilkan daftar perusahaan yang bisa dijadikan partner.
     */
    public function searchCompanies(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $companies = Company::query()
            ->with('profile')
            ->where('status', Company::STATUS_ACTIVE)
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim($request->input('q'));
                $query->whereHas('profile', function ($profileQuery) use ($q) {
                    $profileQuery->where('name', 'like', "%{$q}%")
                        ->orWhere('industry', 'like', "%{$q}%")
                        ->orWhere('city', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('city'), function ($query) use ($request) {
                $query->whereHas('profile', function ($profileQuery) use ($request) {
                    $profileQuery->where('city', $request->input('city'));
                });
            })
            ->orderBy('id')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => CompanyResource::collection($companies),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
        ], 'Daftar perusahaan berhasil diambil.');
    }

    /**
     * GET /api/teacher/partnerships — daftar partnership yang diajukan oleh guru ini.
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $partnerships = SchoolCompanyPartnership::query()
            ->with(['company.profile', 'requester:id,name'])
            ->where('school_id', $teacher->school_id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => PartnershipResource::collection($partnerships),
            'meta' => [
                'current_page' => $partnerships->currentPage(),
                'last_page' => $partnerships->lastPage(),
                'per_page' => $partnerships->perPage(),
                'total' => $partnerships->total(),
            ],
        ], 'Daftar partnership berhasil diambil.');
    }

    /**
     * GET /api/teacher/partnerships/{partnership} — detail partnership.
     */
    public function show(Request $request, SchoolCompanyPartnership $partnership): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        // Hanya partnership dari sekolah guru yang boleh dilihat.
        if ($partnership->school_id !== $teacher->school_id) {
            return $this->error('Anda tidak memiliki akses ke partnership ini.', null, 403);
        }

        $partnership->load(['company.profile', 'requester']);

        return $this->success(new PartnershipResource($partnership), 'Detail partnership berhasil diambil.');
    }

    /**
     * POST /api/teacher/partnerships — kirim partnership request ke perusahaan.
     */
    public function store(StorePartnershipRequest $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $schoolId = $teacher->school_id;
        $companyId = $request->validated('company_id');

        // Pastikan perusahaan aktif.
        $company = Company::find($companyId);
        if (! $company || $company->status !== Company::STATUS_ACTIVE) {
            return $this->error('Perusahaan tidak ditemukan atau tidak aktif.', null, 422);
        }

        // Cek apakah sudah ada partnership antara sekolah dan perusahaan ini.
        $existingPartnership = SchoolCompanyPartnership::where('school_id', $schoolId)
            ->where('company_id', $companyId)
            ->first();

        if ($existingPartnership) {
            if ($existingPartnership->status === SchoolCompanyPartnership::STATUS_PENDING) {
                return $this->error('Anda sudah mengirim request partnership ke perusahaan ini dan sedang menunggu jawaban.', null, 409);
            }

            if ($existingPartnership->status === SchoolCompanyPartnership::STATUS_ACCEPTED) {
                return $this->error('Sekolah Anda sudah memiliki partnership aktif dengan perusahaan ini.', null, 409);
            }

            // Jika status REJECTED atau EXPIRED, boleh mengajukan ulang.
            $existingPartnership->update([
                'status' => SchoolCompanyPartnership::STATUS_PENDING,
                'requested_by' => $request->user()->id,
                'responded_at' => null,
                'notes' => $request->validated('notes'),
            ]);

            return $this->success(
                new PartnershipResource($existingPartnership->fresh(['company.profile', 'requester'])),
                'Partnership request berhasil dikirim ulang.',
                201
            );
        }

        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $schoolId,
            'company_id' => $companyId,
            'requested_by' => $request->user()->id,
            'status' => SchoolCompanyPartnership::STATUS_PENDING,
            'notes' => $request->validated('notes'),
        ]);

        return $this->success(
            new PartnershipResource($partnership->load(['company.profile', 'requester'])),
            'Partnership request berhasil dikirim.',
            201
        );
    }

    /**
     * PUT /api/teacher/partnerships/{partnership} — update status partnership (accept/reject).
     */
    public function update(Request $request, SchoolCompanyPartnership $partnership): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        // Hanya partnership dari sekolah guru yang boleh diupdate.
        if ($partnership->school_id !== $teacher->school_id) {
            return $this->error('Anda tidak memiliki akses ke partnership ini.', null, 403);
        }

        $request->validate([
            'status' => ['required', 'in:ACCEPTED,REJECTED'],
        ]);

        if ($partnership->status !== SchoolCompanyPartnership::STATUS_PENDING) {
            return $this->error('Hanya partnership dengan status PENDING yang dapat diproses.', null, 422);
        }

        $partnership->update([
            'status' => $request->input('status'),
            'responded_at' => now(),
        ]);

        $statusText = $request->input('status') === 'ACCEPTED' ? 'diterima' : 'ditolak';

        return $this->success(
            new PartnershipResource($partnership->fresh(['company.profile', 'requester'])),
            "Partnership berhasil {$statusText}."
        );
    }
}
