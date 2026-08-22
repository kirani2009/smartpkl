<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Student;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 15 — Reporting.
 * Laporan guru: hanya data sekolah tempat guru mengajar (docs/ai/REPORTING.json).
 */
class TeacherReportController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/teacher/reports/placement — Laporan penempatan siswa sekolah.
     */
    public function placement(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $schoolId = $teacher->school_id;

        $students = Student::with(['major:id,name'])
            ->where('school_id', $schoolId)
            ->get()
            ->map(function ($student) {
                $acceptedApplication = $student->applications()
                    ->where('status', Application::STATUS_ACCEPTED)
                    ->with(['internship.company.profile', 'internship.major'])
                    ->first();

                return [
                    'id' => $student->id,
                    'name' => $student->user->name ?? '-',
                    'nis' => $student->nis,
                    'class' => $student->class,
                    'major' => $student->major->name ?? '-',
                    'is_placed' => $acceptedApplication !== null,
                    'name' => $acceptedApplication?->internship?->company?->profile?->name ?? '-',
                    'internship_title' => $acceptedApplication?->internship?->title ?? '-',
                ];
            });

        $totalStudents = $students->count();
        $placedStudents = $students->filter(fn ($s) => $s['is_placed'])->count();
        $unplacedStudents = $totalStudents - $placedStudents;
        $placementRate = $totalStudents > 0 ? round(($placedStudents / $totalStudents) * 100, 2) : 0;

        $byMajor = $students->groupBy('major')->map(function ($group, $majorName) {
            $total = $group->count();
            $placed = $group->filter(fn ($s) => $s['is_placed'])->count();
            return [
                'major' => $majorName,
                'total' => $total,
                'placed' => $placed,
                'unplaced' => $total - $placed,
                'placement_rate' => $total > 0 ? round(($placed / $total) * 100, 2) : 0,
            ];
        })->values();

        return $this->success([
            'school' => $teacher->school->name,
            'summary' => [
                'total_students' => $totalStudents,
                'placed' => $placedStudents,
                'unplaced' => $unplacedStudents,
                'placement_rate' => $placementRate,
            ],
            'by_major' => $byMajor,
            'details' => $students,
        ], 'Laporan penempatan berhasil diambil.');
    }

    /**
     * GET /api/teacher/reports/no-internship — Siswa sekolah tanpa PKL.
     */
    public function noInternship(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $schoolId = $teacher->school_id;

        $query = Student::with(['major:id,name'])
            ->where('school_id', $schoolId)
            ->whereDoesntHave('applications', function ($q) {
                $q->where('status', Application::STATUS_ACCEPTED);
            });

        if ($request->filled('major_id')) {
            $query->where('major_id', $request->input('major_id'));
        }

        $students = $query->get()->map(function ($student) {
            $totalApplications = $student->applications()->count();
            $rejectedCount = $student->applications()->where('status', Application::STATUS_REJECTED)->count();

            return [
                'id' => $student->id,
                'name' => $student->user->name ?? '-',
                'nis' => $student->nis,
                'class' => $student->class,
                'major' => $student->major->name ?? '-',
                'total_applications' => $totalApplications,
                'rejected_count' => $rejectedCount,
                'status' => $totalApplications === 0 ? 'belum_melamar' : 'belum_diterima',
            ];
        });

        return $this->success([
            'school' => $teacher->school->name,
            'total' => $students->count(),
            'by_status' => [
                'belum_melamar' => $students->filter(fn ($s) => $s['status'] === 'belum_melamar')->count(),
                'belum_diterima' => $students->filter(fn ($s) => $s['status'] === 'belum_diterima')->count(),
            ],
            'students' => $students,
        ], 'Daftar siswa tanpa PKL berhasil diambil.');
    }

    /**
     * GET /api/teacher/reports/applications — Laporan lamaran siswa sekolah.
     */
    public function applications(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $schoolId = $teacher->school_id;

        $query = Application::with([
            'student:id,user_id,major_id',
            'student.user:id,name',
            'student.major:id,name',
            'internship:id,title',
            'internship.company:id',
            'internship.company.profile:company_id,name',
        ])->whereHas('student', fn ($q) => $q->where('school_id', $schoolId));

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('major_id')) {
            $query->whereHas('student', fn ($q) => $q->where('major_id', $request->input('major_id')));
        }

        $applications = $query->latest('applied_at')->get();

        $summary = [
            'total' => $applications->count(),
            'pending' => $applications->filter(fn ($a) => $a->status === Application::STATUS_PENDING)->count(),
            'reviewed' => $applications->filter(fn ($a) => $a->status === Application::STATUS_REVIEWED)->count(),
            'interview' => $applications->filter(fn ($a) => $a->status === Application::STATUS_INTERVIEW)->count(),
            'accepted' => $applications->filter(fn ($a) => $a->status === Application::STATUS_ACCEPTED)->count(),
            'rejected' => $applications->filter(fn ($a) => $a->status === Application::STATUS_REJECTED)->count(),
        ];

        $byMajor = $applications->groupBy(fn ($a) => $a->student->major->name ?? '-')->map(function ($group, $majorName) {
            return [
                'major' => $majorName,
                'total' => $group->count(),
                'accepted' => $group->filter(fn ($a) => $a->status === Application::STATUS_ACCEPTED)->count(),
                'rejected' => $group->filter(fn ($a) => $a->status === Application::STATUS_REJECTED)->count(),
            ];
        })->values();

        return $this->success([
            'school' => $teacher->school->name,
            'summary' => $summary,
            'by_major' => $byMajor,
        ], 'Laporan lamaran berhasil diambil.');
    }
}
