<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

/**
 * PHASE 5 — Company.
 * Perusahaan hanya boleh mengelola profil dan data sendiri.
 * Admin boleh mengakses semua perusahaan.
 */
class CompanyPolicy
{
    /**
     * Guru bisa melihat daftar perusahaan (untuk mencari partner).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Melihat detail perusahaan — semua user login boleh.
     */
    public function view(User $user, Company $company): bool
    {
        return true;
    }

    /**
     * Membuat profil perusahaan — hanya role company (profil sendiri).
     */
    public function create(User $user): bool
    {
        return $user->isCompany();
    }

    /**
     * Update perusahaan — hanya pemilik atau admin.
     */
    public function update(User $user, Company $company): bool
    {
        return $user->isAdmin() || $this->owns($user, $company);
    }

    /**
     * Delete perusahaan — hanya admin.
     */
    public function delete(User $user, Company $company): bool
    {
        return $user->isAdmin();
    }

    /**
     * Accept/reject partnership — hanya pemilik perusahaan.
     */
    public function managePartnership(User $user, Company $company): bool
    {
        return $this->owns($user, $company);
    }

    /**
     * Cek apakah user adalah pemilik perusahaan ini.
     */
    private function owns(User $user, Company $company): bool
    {
        return $user->isCompany() && $user->company?->id === $company->id;
    }
}
