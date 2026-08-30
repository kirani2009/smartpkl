<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\SchoolCompanyPartnership;
use App\Models\Student;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        $schoolName = $teacher->school_name ?? $teacher->school?->name ?? '';

        // If no school info at all, return empty dashboard
        if (! $schoolId && ! $schoolName) {
            return $this->success([
                'school' => ['id' => null, 'name' => $teacher->teacher_name ? "Sekolah {$teacher->teacher_name}" : null],
                'students' => ['total' => 0, 'placed' => 0, 'without_internship' => 0],
                'partnerships' => ['total' => 0, 'active' => 0, 'pending' => 0],
                'recent_applications' => [],
            ], 'Profil guru belum lengkap.');
        }

        $totalStudents = Student::where(function ($q) use ($schoolId, $schoolName) {
            if ($schoolId) {
                $q->where('school_id', $schoolId);
            }
            if ($schoolName) {
                $q->orWhere('school_name', $schoolName);
            }
        })->count();

        $acceptedStudentIds = Application::where('status', Application::STATUS_ACCEPTED)
            ->whereHas('student', function ($q) use ($schoolId, $schoolName) {
                $q->where(function ($sq) use ($schoolId, $schoolName) {
                    if ($schoolId) {
                        $sq->where('school_id', $schoolId);
                    }
                    if ($schoolName) {
                        $sq->orWhere('school_name', $schoolName);
                    }
                });
            })
            ->distinct()
            ->pluck('student_id');

        $studentsPlaced = $acceptedStudentIds->count();
        $studentsWithoutInternship = max(0, $totalStudents - $studentsPlaced);

        $recentApplications = Application::query()
            ->with(['student.user:id,name', 'internship:id,title'])
            ->whereHas('student', function ($q) use ($schoolId, $schoolName) {
                $q->where(function ($sq) use ($schoolId, $schoolName) {
                    if ($schoolId) {
                        $sq->where('school_id', $schoolId);
                    }
                    if ($schoolName) {
                        $sq->orWhere('school_name', $schoolName);
                    }
                });
            })
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
                'id' => $schoolId,
                'name' => $schoolName,
            ],
            'students' => [
                'total' => $totalStudents,
                'placed' => $studentsPlaced,
                'without_internship' => $studentsWithoutInternship,
            ],
            'partnerships' => [
                'total' => SchoolCompanyPartnership::query()
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
                    ->count(),
                'active' => SchoolCompanyPartnership::query()
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
                    ->count(),
                'pending' => SchoolCompanyPartnership::query()
                    ->where('status', SchoolCompanyPartnership::STATUS_PENDING)
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
                    ->count(),
            ],
            'recent_applications' => $recentApplications,
        ], 'Dashboard guru berhasil diambil.');
    }
}
