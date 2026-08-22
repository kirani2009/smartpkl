<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\SchoolCompanyPartnership;
use App\Models\Student;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 4 — School & Teacher.
 * Dashboard guru: ringkasan data sekolah tempat guru mengajar.
 * Hanya guru (ROLES.json: teacher memantau siswa & partnership sekolahnya).
 */
class TeacherDashboardController extends Controller
{
    use ApiResponseTrait;

    public function show(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat. Silakan lengkapi profil terlebih dahulu.', null, 404);
        }

        $schoolId = $teacher->school_id;

        $totalStudents = Student::where('school_id', $schoolId)->count();

        $acceptedStudentIds = Application::where('status', Application::STATUS_ACCEPTED)
            ->whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
            ->distinct()
            ->pluck('student_id');

        $studentsPlaced = $acceptedStudentIds->count();
        $studentsWithoutInternship = max(0, $totalStudents - $studentsPlaced);

        $recentApplications = Application::query()
            ->with(['student.user:id,name', 'internship:id,title'])
            ->whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
            ->latest('applied_at')
            ->limit(5)
            ->get()
            ->map(fn (Application $application) => [
                'id' => $application->id,
                'status' => $application->status,
                'applied_at' => $application->applied_at?->toISOString(),
                'student_name' => $application->student->user->name,
                'internship_title' => $application->internship->title,
            ]);

        return $this->success([
            'school' => [
                'id' => $teacher->school->id,
                'name' => $teacher->school->name,
            ],
            'students' => [
                'total' => $totalStudents,
                'placed' => $studentsPlaced,
                'without_internship' => $studentsWithoutInternship,
            ],
            'partnerships' => [
                'total' => SchoolCompanyPartnership::where('school_id', $schoolId)->count(),
                'active' => SchoolCompanyPartnership::where('school_id', $schoolId)
                    ->where('status', SchoolCompanyPartnership::STATUS_ACCEPTED)
                    ->count(),
                'pending' => SchoolCompanyPartnership::where('school_id', $schoolId)
                    ->where('status', SchoolCompanyPartnership::STATUS_PENDING)
                    ->count(),
            ],
            'recent_applications' => $recentApplications,
        ], 'Dashboard guru berhasil diambil.');
    }
}
