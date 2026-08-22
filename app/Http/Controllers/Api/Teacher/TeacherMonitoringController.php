<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Application;
use App\Models\InternshipListing;
use App\Models\SchoolCompanyPartnership;
use App\Models\Student;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 12 — Teacher Monitoring.
 * Guru memantau siswa: daftar siswa, status penempatan, overview sekolah.
 */
class TeacherMonitoringController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/teacher/monitoring/overview — overview lengkap sekolah.
     */
    public function overview(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $schoolId = $teacher->school_id;

        // Student stats by major
        $studentsByMajor = Student::where('school_id', $schoolId)
            ->selectRaw('major_id, count(*) as total')
            ->groupBy('major_id')
            ->pluck('total', 'major_id');

        // Application stats
        $applicationStats = Application::query()
            ->whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalStudents = Student::where('school_id', $schoolId)->count();

        $placedStudentIds = Application::where('status', Application::STATUS_ACCEPTED)
            ->whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
            ->distinct()
            ->pluck('student_id');

        $placed = $placedStudentIds->count();

        // Active partnerships
        $partnershipsActive = SchoolCompanyPartnership::where('school_id', $schoolId)
            ->where('status', SchoolCompanyPartnership::STATUS_ACCEPTED)
            ->count();

        // Published internships from active partners
        $availableInternships = InternshipListing::query()
            ->where('status', 'PUBLISHED')
            ->where(function ($q) use ($schoolId) {
                $q->whereNull('school_id')
                    ->orWhere('school_id', $schoolId);
            })
            ->count();

        return $this->success([
            'school' => [
                'id' => $teacher->school->id,
                'name' => $teacher->school->name,
            ],
            'students' => [
                'total' => $totalStudents,
                'placed' => $placed,
                'without_internship' => max(0, $totalStudents - $placed),
                'placement_rate' => $totalStudents > 0
                    ? round($placed / $totalStudents * 100, 1)
                    : 0,
                'by_major' => $studentsByMajor,
            ],
            'applications' => [
                'total' => $applicationStats->sum(),
                'pending' => $applicationStats->get('PENDING', 0),
                'reviewed' => $applicationStats->get('REVIEWED', 0),
                'interview' => $applicationStats->get('INTERVIEW', 0),
                'accepted' => $applicationStats->get('ACCEPTED', 0),
                'rejected' => $applicationStats->get('REJECTED', 0),
            ],
            'partnerships' => [
                'active' => $partnershipsActive,
            ],
            'available_internships' => $availableInternships,
        ], 'Overview monitoring berhasil diambil.');
    }

    /**
     * GET /api/teacher/monitoring/students — daftar siswa beserta status PKL.
     */
    public function students(Request $request): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $schoolId = $teacher->school_id;

        $students = Student::query()
            ->with(['user:id,name,email', 'major:id,name'])
            ->where('school_id', $schoolId)
            ->when($request->filled('major_id'), fn ($q) => $q->where('major_id', $request->input('major_id')))
            ->when($request->filled('placement'), function ($query) use ($request) {
                $placement = $request->input('placement');
                if ($placement === 'placed') {
                    $query->whereIn('id', function ($subQ) {
                        $subQ->select('student_id')
                            ->from('applications')
                            ->where('status', Application::STATUS_ACCEPTED);
                    });
                } elseif ($placement === 'unplaced') {
                    $query->whereNotIn('id', function ($subQ) {
                        $subQ->select('student_id')
                            ->from('applications')
                            ->where('status', Application::STATUS_ACCEPTED);
                    });
                }
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim($request->input('q'));
                $query->whereHas('user', function ($userQ) use ($q) {
                    $userQ->where('name', 'like', "%{$q}%");
                })->orWhere('nis', 'like', "%{$q}%");
            })
            ->withCount([
                'applications as total_applications',
                'applications as accepted_applications' => fn ($q) => $q->where('status', Application::STATUS_ACCEPTED),
            ])
            ->orderBy('id')
            ->paginate($request->integer('per_page', 15));

        // Add placement info
        $items = $students->getCollection()->map(function (Student $student) {
            $acceptedApp = $student->applications
                ->where('status', Application::STATUS_ACCEPTED)
                ->first();

            return [
                'id' => $student->id,
                'nis' => $student->nis,
                'class' => $student->class,
                'gender' => $student->gender,
                'user' => $student->user,
                'major' => $student->major,
                'total_applications' => $student->total_applications,
                'accepted_applications' => $student->accepted_applications,
                'is_placed' => $student->accepted_applications > 0,
                'placement' => $acceptedApp ? [
                    'internship_id' => $acceptedApp->internship_id,
                    'internship_title' => $acceptedApp->internship->title ?? null,
                    'company_name' => $acceptedApp->internship->company->profile->name ?? null,
                ] : null,
            ];
        });

        return $this->success([
            'items' => $items,
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ], 'Daftar siswa berhasil diambil.');
    }

    /**
     * GET /api/teacher/monitoring/students/{student} — detail siswa beserta semua lamaran.
     */
    public function showStudent(Request $request, Student $student): JsonResponse
    {
        $teacher = $request->user()->teacher;

        if (! $teacher || $student->school_id !== $teacher->school_id) {
            return $this->error('Anda tidak memiliki akses ke data siswa ini.', null, 403);
        }

        $student->load([
            'user:id,name,email',
            'school:id,name',
            'major:id,name',
            'skills',
            'portfolios',
            'documents',
            'certificates',
            'applications.internship.company.profile',
            'applications.statusHistories.changedBy',
        ]);

        return $this->success(new StudentResource($student), 'Detail siswa berhasil diambil.');
    }
}
