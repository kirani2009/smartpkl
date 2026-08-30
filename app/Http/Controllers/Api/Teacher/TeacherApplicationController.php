<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 9 — Application.
 * Guru memantau lamaran siswa dari sekolahnya.
 */
class TeacherApplicationController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/teacher/applications — daftar lamaran siswa dari sekolah guru.
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $applications = Application::query()
            ->with([
                'student.user:id,name',
                'student.school:id,name',
                'student.major:id,name',
                'internship:id,title,company_id',
                'internship.company:id',
                'internship.company.profile:name',
            ])
            ->whereHas('student', function ($q) use ($teacher) {
                $schoolName = $teacher->school_name ?? $teacher->school?->name ?? '';
                $q->where(function ($sq) use ($teacher, $schoolName) {
                    $sq->where('school_id', $teacher->school_id)
                       ->orWhere('school_name', $schoolName);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('student_id'), fn ($q) => $q->where('student_id', $request->input('student_id')))
            ->latest('applied_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => ApplicationResource::collection($applications),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ], 'Daftar lamaran siswa berhasil diambil.');
    }

    /**
     * GET /api/teacher/applications/{application} — detail lamaran siswa.
     */
    public function show(Request $request, Application $application): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        // Pastikan lamaran dari siswa sekolah guru
        $schoolName = $teacher->school_name ?? $teacher->school?->name ?? '';
        $hasAccess = $application->student->school_id === $teacher->school_id
            || ($application->student->school_name && $application->student->school_name === $schoolName);

        if (! $hasAccess) {
            return $this->error('Anda tidak memiliki akses ke lamaran ini.', null, 403);
        }

        $application->load([
            'student.user', 'student.school', 'student.major', 'student.skills',
            'internship.company.profile', 'internship.school', 'internship.major',
            'internship.requirements', 'statusHistories.changedBy', 'interviews',
        ]);

        return $this->success(new ApplicationResource($application), 'Detail lamaran berhasil diambil.');
    }

    /**
     * GET /api/teacher/applications/stats — statistik lamaran siswa sekolah.
     */
    public function stats(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $schoolId = $teacher->school_id;
        $schoolName = $teacher->school_name ?? $teacher->school?->name ?? '';

        $stats = Application::query()
            ->whereHas('student', function ($q) use ($schoolId, $schoolName) {
                $q->where(function ($sq) use ($schoolId, $schoolName) {
                    $sq->where('school_id', $schoolId)
                       ->orWhere('school_name', $schoolName);
                });
            })
            ->selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = $stats->sum();

        return $this->success([
            'total' => $total,
            'pending' => $stats->get('PENDING', 0),
            'reviewed' => $stats->get('REVIEWED', 0),
            'interview' => $stats->get('INTERVIEW', 0),
            'accepted' => $stats->get('ACCEPTED', 0),
            'rejected' => $stats->get('REJECTED', 0),
        ], 'Statistik lamaran berhasil diambil.');
    }
}
