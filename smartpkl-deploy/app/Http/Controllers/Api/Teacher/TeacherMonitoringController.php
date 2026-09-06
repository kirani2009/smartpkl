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

        $schoolName = $teacher->school_name ?? $teacher->school?->name ?? '';

        // Student stats by major
        $studentsByMajor = Student::where(function ($q) use ($schoolId, $schoolName) {
                $q->where('school_id', $schoolId)
                  ->orWhere('school_name', $schoolName);
            })
            ->selectRaw('major_id, count(*) as total')
            ->groupBy('major_id')
            ->pluck('total', 'major_id');

        // Application stats
        $applicationStats = Application::query()
            ->whereHas('student', function ($q) use ($schoolId, $schoolName) {
                $q->where(function ($sq) use ($schoolId, $schoolName) {
                    $sq->where('school_id', $schoolId)
                       ->orWhere('school_name', $schoolName);
                });
            })
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalStudents = Student::where(function ($q) use ($schoolId, $schoolName) {
                $q->where('school_id', $schoolId)
                  ->orWhere('school_name', $schoolName);
            })->count();

        $placedStudentIds = Application::where('status', Application::STATUS_ACCEPTED)
            ->whereHas('student', function ($q) use ($schoolId, $schoolName) {
                $q->where(function ($sq) use ($schoolId, $schoolName) {
                    $sq->where('school_id', $schoolId)
                       ->orWhere('school_name', $schoolName);
                });
            })
            ->distinct()
            ->pluck('student_id');

        $placed = $placedStudentIds->count();

        // Active partnerships
        $partnershipsActive = SchoolCompanyPartnership::query()
            ->where('status', SchoolCompanyPartnership::STATUS_ACCEPTED)
            ->where(function ($q) use ($schoolId, $schoolName) {
                if ($schoolId) {
                    $q->where('school_id', $schoolId);
                }
                if ($schoolName) {
                    $q->orWhereHas('school', function ($sq) use ($schoolName) {
                        $sq->where('name', $schoolName);
                    });
                }
            })
            ->count();

        // Published internships from active partners
        $availableInternships = $schoolId
            ? InternshipListing::query()
                ->where('status', 'PUBLISHED')
                ->where(function ($q) use ($schoolId) {
                    $q->whereNull('school_id')
                        ->orWhere('school_id', $schoolId);
                })
                ->count()
            : InternshipListing::where('status', 'PUBLISHED')->count();

        return $this->success([
            'school' => [
                'id' => $schoolId,
                'name' => $schoolName,
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

        $schoolName = $teacher->school_name ?? $teacher->school?->name ?? '';

        $query = Student::query()
            ->with(['user:id,name,email', 'major:id,name'])
            ->where(function ($q) use ($schoolId, $schoolName) {
                if ($schoolId) {
                    $q->where('school_id', $schoolId);
                }
                if ($schoolName) {
                    $q->orWhere('school_name', $schoolName);
                }
            })
            ->when($request->filled('major_id'), function ($q) use ($request) {
                $majorId = $request->input('major_id');
                $majorName = \App\Models\Major::find($majorId)?->name;
                $q->where(function ($mq) use ($majorId, $majorName) {
                    $mq->where('major_id', $majorId)
                       ->when($majorName, fn ($sub) => $sub->orWhere('major_name', $majorName));
                });
            })
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
            ->orderBy('id');

        $students = $query->paginate($request->integer('per_page', 15));

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
                'major_name' => $student->major?->name ?? $student->major_name,
                'school_name' => $student->school?->name ?? $student->school_name,
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

        if (! $teacher) {
            return $this->error('Profil guru belum dibuat.', null, 404);
        }

        $schoolName = $teacher->school_name ?? $teacher->school?->name ?? '';
        $hasAccess = ($teacher->school_id && $student->school_id === $teacher->school_id)
            || ($schoolName && $student->school_name === $schoolName);

        if (! $hasAccess) {
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

        // Determine PKL placement status from applications
        $acceptedApp = $student->applications->first(fn ($a) => $a->status === Application::STATUS_ACCEPTED);
        $totalApps = $student->applications->count();
        $placement = null;
        if ($acceptedApp) {
            $placement = [
                'internship_id' => $acceptedApp->internship_id,
                'internship_title' => $acceptedApp->internship->title ?? null,
                'company_name' => $acceptedApp->internship->company->profile->name ?? null,
            ];
        }

        $studentData = (new StudentResource($student))->toArray($request);
        $studentData['is_placed'] = $acceptedApp !== null;
        $studentData['total_applications'] = $totalApps;
        $studentData['placement'] = $placement;

        return $this->success($studentData, 'Detail siswa berhasil diambil.');
    }
}
