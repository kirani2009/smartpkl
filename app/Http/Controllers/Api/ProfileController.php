<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

/**
 * PHASE 2 — User profile.
 *
 * User hanya dapat mengubah data profilnya sendiri.
 */
class ProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * PUT /api/me
     * Memperbarui profil user yang sedang login (nama, email, dan/atau password).
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update($request->safe()->except('current_password'));

        return $this->success(new UserResource($user->fresh()), 'Profil berhasil diperbarui.');
    }
}
