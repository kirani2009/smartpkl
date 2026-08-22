<?php

namespace App\Policies;

use App\Models\Major;
use App\Models\School;
use App\Models\User;

/**
 * PHASE 4 — School & Teacher.
 * Pengelolaan jurusan: guru dari sekolah terkait atau admin.
 * Authorization diperiksa di backend (docs/ai/SECURITY.json).
 */
class MajorPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Semua user login bisa melihat daftar jurusan.
    }

    public function view(User $user, Major $major): bool
    {
        return true;
    }

    /**
     * Membuat jurusan untuk sebuah sekolah.
     */
    public function create(User $user, School $school): bool
    {
        return $user->isAdmin() || $this->isTeacherOf($user, $school->id);
    }

    public function update(User $user, Major $major): bool
    {
        return $user->isAdmin() || $this->isTeacherOf($user, $major->school_id);
    }

    public function delete(User $user, Major $major): bool
    {
        return $user->isAdmin() || $this->isTeacherOf($user, $major->school_id);
    }

    private function isTeacherOf(User $user, ?int $schoolId): bool
    {
        return $user->isTeacher()
            && $schoolId !== null
            && $user->teacher?->school_id === $schoolId;
    }
}
