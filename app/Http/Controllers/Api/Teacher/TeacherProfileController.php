<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherProfileRequest;
use App\Http\Requests\Teacher\UpdateTeacherProfileRequest;
use App\Http\Resources\TeacherResource;
use App\Models\School;
use App\Models\Teacher;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherProfileController extends Controller
{
    use ApiResponseTrait;

    public function show(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat. Silakan lengkapi profil terlebih dahulu.', null, 404);
        }

        $teacher->load('school');

        return $this->success(new TeacherResource($teacher), 'Profil guru berhasil diambil.');
    }

    public function store(StoreTeacherProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Auto-match school_id from school_name if not provided
        if (empty($validated['school_id']) && ! empty($validated['school_name'])) {
            $school = School::where('name', $validated['school_name'])->first();
            if ($school) {
                $validated['school_id'] = $school->id;
            }
        }

        $teacher = $user->teacher;
        if ($teacher && $teacher->school_id) {
            return $this->error('Profil guru sudah ada. Gunakan PUT untuk memperbarui.', null, 409);
        }

        if ($teacher) {
            $teacher->update($validated);
        } else {
            $teacher = $user->teacher()->create($validated);
        }

        $teacher->load('school');

        return $this->success(new TeacherResource($teacher), 'Profil guru berhasil dibuat.', 201);
    }

    public function update(UpdateTeacherProfileRequest $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum ada. Gunakan POST untuk membuat profil.', null, 404);
        }

        $validated = $request->validated();

        // Auto-match school_id from school_name if school_name changed
        if (! empty($validated['school_name']) && empty($validated['school_id'])) {
            $school = School::where('name', $validated['school_name'])->first();
            $validated['school_id'] = $school?->id;
        }

        $teacher->update($validated);
        $teacher->load('school');

        return $this->success(new TeacherResource($teacher->fresh()), 'Profil guru berhasil diperbarui.');
    }
}
