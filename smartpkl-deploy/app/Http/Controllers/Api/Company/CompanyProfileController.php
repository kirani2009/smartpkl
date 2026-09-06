<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreCompanyProfileRequest;
use App\Http\Requests\Company\UpdateCompanyProfileRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 5 — Company.
 * Profil perusahaan (hanya profil milik sendiri).
 */
class CompanyProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/me/company — profil perusahaan yang sedang login.
     */
    public function show(Request $request): JsonResponse
    {
        $company = Company::where('user_id', $request->user()->id)
            ->with('profile')
            ->first();

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat. Silakan lengkapi profil terlebih dahulu.', null, 404);
        }

        return $this->success(new CompanyResource($company), 'Profil perusahaan berhasil diambil.');
    }

    /**
     * POST /api/me/company — buat profil perusahaan.
     */
    public function store(StoreCompanyProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $company = Company::where('user_id', $user->id)->first();

        // Jika company sudah ada tapi belum ada profile (skeleton dari registrasi),
        // buat profile-nya.
        if ($company && $company->profile) {
            return $this->error('Profil perusahaan sudah ada. Gunakan PUT untuk memperbarui.', null, 409);
        }

        if (! $company) {
            $company = Company::create([
                'user_id' => $user->id,
                'status' => Company::STATUS_ACTIVE,
            ]);
        }

        $validated = $request->validated();

        $company->profile()->create($validated);
        $company->load('profile');

        return $this->success(new CompanyResource($company), 'Profil perusahaan berhasil dibuat.', 201);
    }

    /**
     * PUT /api/me/company — perbarui profil perusahaan.
     */
    public function update(UpdateCompanyProfileRequest $request): JsonResponse
    {
        $company = Company::where('user_id', $request->user()->id)
            ->with('profile')
            ->first();

        if (! $company) {
            return $this->error('Profil perusahaan belum ada. Gunakan POST untuk membuat profil.', null, 404);
        }

        $validated = $request->validated();

        if ($company->profile) {
            $company->profile->update($validated);
        } else {
            $company->profile()->create($validated);
        }

        $company->load('profile');

        return $this->success(new CompanyResource($company), 'Profil perusahaan berhasil diperbarui.');
    }
}
