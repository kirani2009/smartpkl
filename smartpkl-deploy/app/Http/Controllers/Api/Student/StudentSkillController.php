<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\Student;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 8 — Student Profile.
 * Mengelola skill siswa (tambah, update level, hapus).
 */
class StudentSkillController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/me/student/skills — daftar skill siswa.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $skills = $student->skills()->get();

        return $this->success([
            'items' => $skills,
        ], 'Daftar skill berhasil diambil.');
    }

    /**
     * PUT /api/me/student/skills — update semua skill siswa (sync).
     * Body: { "skills": [{ "skill_id": 1, "level": "advanced" }, ...] }
     */
    public function sync(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $request->validate([
            'skills' => ['required', 'array', 'max:20'],
            'skills.*.skill_id' => ['required', 'exists:skills,id'],
            'skills.*.level' => ['nullable', 'in:beginner,intermediate,advanced'],
        ]);

        $syncData = [];
        foreach ($request->input('skills') as $item) {
            $syncData[$item['skill_id']] = ['level' => $item['level'] ?? null];
        }

        $student->skills()->sync($syncData);

        // Re-fetch with pivot data
        $skills = collect($syncData)->map(function ($pivotData, $skillId) {
            $skill = Skill::find($skillId);
            return [
                'id' => $skill->id,
                'name' => $skill->name,
                'level' => $pivotData['level'] ?? null,
            ];
        })->values();

        return $this->success([
            'items' => $skills,
        ], 'Skill berhasil diperbarui.');
    }

    /**
     * POST /api/me/student/skills — tambah satu skill.
     */
    public function store(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $request->validate([
            'skill_id' => ['required', 'exists:skills,id'],
            'level' => ['nullable', 'in:beginner,intermediate,advanced'],
        ]);

        $skillId = $request->input('skill_id');
        $level = $request->input('level');

        // Cek apakah sudah ada
        if ($student->skills()->where('skill_id', $skillId)->exists()) {
            return $this->error('Skill sudah ada. Gunakan PUT untuk memperbarui level.', null, 409);
        }

        $student->skills()->attach($skillId, ['level' => $level]);

        $skill = Skill::find($skillId);

        return $this->success([
            'id' => $skill->id,
            'name' => $skill->name,
            'level' => $level,
        ], 'Skill berhasil ditambahkan.', 201);
    }

    /**
     * DELETE /api/me/student/skills/{skill} — hapus skill dari profil.
     */
    public function destroy(Request $request, Skill $skill): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $detached = $student->skills()->detach($skill->id);

        if ($detached === 0) {
            return $this->error('Skill tidak ditemukan di profil Anda.', null, 404);
        }

        return $this->success(null, 'Skill berhasil dihapus.');
    }
}
