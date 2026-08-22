<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReportResource;
use App\Models\Application;
use App\Models\Company;
use App\Models\InternshipListing;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\Student;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 15 — Reporting.
 * Laporan platform untuk admin (docs/ai/REPORTING.json).
 */
class AdminReportController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/admin/reports/overview — Ringkasan platform.
     */
    public function overview(): JsonResponse
    {
        $data = [
            'users' => [
                'total' => User::count(),
                'students' => User::where('role', 'student')->count(),
                'teachers' => User::where('role', 'teacher')->count(),
                'companies' => User::where('role', 'company')->count(),
                'admins' => User::where('role', 'admin')->count(),
            ],
            'schools' => [
                'total' => School::count(),
                'with_partnerships' => School::whereHas('partnerships', function ($q) {
                    $q->where('status', SchoolCompanyPartnership::STATUS_ACCEPTED);
                })->count(),
            ],
            'companies' => [
                'total' => Company::count(),
                'active' => Company::where('status', Company::STATUS_ACTIVE)->count(),
                'suspended' => Company::where('status', Company::STATUS_SUSPENDED)->count(),
            ],
            'students' => [
                'total' => Student::count(),
                'with_profiles' => Student::whereNotNull('nis')->count(),
                'without_profiles' => Student::whereNull('nis')->count(),
            ],
            'partnerships' => [
                'total' => SchoolCompanyPartnership::count(),
                'active' => SchoolCompanyPartnership::where('status', SchoolCompanyPartnership::STATUS_ACCEPTED)->count(),
                'pending' => SchoolCompanyPartnership::where('status', SchoolCompanyPartnership::STATUS_PENDING)->count(),
                'rejected' => SchoolCompanyPartnership::where('status', SchoolCompanyPartnership::STATUS_REJECTED)->count(),
            ],
            'internships' => [
                'total' => InternshipListing::count(),
                'published' => InternshipListing::where('status', InternshipListing::STATUS_PUBLISHED)->count(),
                'draft' => InternshipListing::where('status', InternshipListing::STATUS_DRAFT)->count(),
                'closed' => InternshipListing::where('status', InternshipListing::STATUS_CLOSED)->count(),
            ],
            'applications' => [
                'total' => Application::count(),
                'pending' => Application::where('status', Application::STATUS_PENDING)->count(),
                'reviewed' => Application::where('status', Application::STATUS_REVIEWED)->count(),
                'interview' => Application::where('status', Application::STATUS_INTERVIEW)->count(),
                'accepted' => Application::where('status', Application::STATUS_ACCEPTED)->count(),
                'rejected' => Application::where('status', Application::STATUS_REJECTED)->count(),
            ],
        ];

        return $this->success($data, 'Ringkasan platform berhasil diambil.');
    }

    /**
     * GET /api/admin/reports/placement — Laporan penempatan siswa.
     */
    public function placement(Request $request): JsonResponse
    {
        $query = Student::with(['school:id,name', 'major:id,name']);

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        $students = $query->get()->map(function ($student) {
            $acceptedApplication = $student->applications()
                ->where('status', Application::STATUS_ACCEPTED)
                ->with(['internship.company.profile', 'internship.major'])
                ->first();

            return [
                'id' => $student->id,
                'name' => $student->user->name ?? '-',
                'nis' => $student->nis,
                'class' => $student->class,
                'school' => $student->school->name ?? '-',
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

        $bySchool = $students->groupBy('school')->map(function ($group, $schoolName) {
            $total = $group->count();
            $placed = $group->filter(fn ($s) => $s['is_placed'])->count();
            return [
                'school' => $schoolName,
                'total' => $total,
                'placed' => $placed,
                'unplaced' => $total - $placed,
                'placement_rate' => $total > 0 ? round(($placed / $total) * 100, 2) : 0,
            ];
        })->values();

        return $this->success([
            'summary' => [
                'total_students' => $totalStudents,
                'placed' => $placedStudents,
                'unplaced' => $unplacedStudents,
                'placement_rate' => $placementRate,
            ],
            'by_school' => $bySchool,
            'details' => $students,
        ], 'Laporan penempatan berhasil diambil.');
    }

    /**
     * GET /api/admin/reports/no-internship — Siswa tanpa PKL.
     */
    public function noInternship(Request $request): JsonResponse
    {
        $query = Student::with(['school:id,name', 'major:id,name'])
            ->whereDoesntHave('applications', function ($q) {
                $q->where('status', Application::STATUS_ACCEPTED);
            });

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

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
                'school' => $student->school->name ?? '-',
                'major' => $student->major->name ?? '-',
                'total_applications' => $totalApplications,
                'rejected_count' => $rejectedCount,
                'status' => $totalApplications === 0 ? 'belum_melamar' : 'belum_diterima',
            ];
        });

        return $this->success([
            'total' => $students->count(),
            'by_status' => [
                'belum_melamar' => $students->filter(fn ($s) => $s['status'] === 'belum_melamar')->count(),
                'belum_diterima' => $students->filter(fn ($s) => $s['status'] === 'belum_diterima')->count(),
            ],
            'students' => $students,
        ], 'Daftar siswa tanpa PKL berhasil diambil.');
    }

    /**
     * GET /api/admin/reports/partnerships — Laporan partnership.
     */
    public function partnerships(Request $request): JsonResponse
    {
        $query = SchoolCompanyPartnership::with([
            'school:id,name',
            'company:id',
            'company.profile:company_id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        $partnerships = $query->get();

        $summary = [
            'total' => $partnerships->count(),
            'active' => $partnerships->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_ACCEPTED)->count(),
            'pending' => $partnerships->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_PENDING)->count(),
            'rejected' => $partnerships->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_REJECTED)->count(),
            'expired' => $partnerships->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_EXPIRED)->count(),
        ];

        $bySchool = $partnerships->groupBy(fn ($p) => $p->school->name ?? '-')->map(function ($group, $schoolName) {
            return [
                'school' => $schoolName,
                'total' => $group->count(),
                'active' => $group->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_ACCEPTED)->count(),
                'pending' => $group->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_PENDING)->count(),
            ];
        })->values();

        $byCompany = $partnerships->groupBy(fn ($p) => $p->company->profile->name ?? '-')->map(function ($group, $companyName) {
            return [
                'company' => $companyName,
                'total' => $group->count(),
                'active' => $group->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_ACCEPTED)->count(),
                'pending' => $group->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_PENDING)->count(),
            ];
        })->values();

        return $this->success([
            'summary' => $summary,
            'by_school' => $bySchool,
            'by_company' => $byCompany,
        ], 'Laporan partnership berhasil diambil.');
    }

    /**
     * GET /api/admin/reports/internships — Laporan lowongan PKL.
     */
    public function internships(Request $request): JsonResponse
    {
        $query = InternshipListing::with(['company:id', 'company.profile:company_id,name', 'school:id,name', 'major:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        $internships = $query->get()->map(function ($internship) {
            $applicationCount = $internship->applications()->count();
            $acceptedCount = $internship->applications()->where('status', Application::STATUS_ACCEPTED)->count();

            return [
                'id' => $internship->id,
                'title' => $internship->title,
                'company' => $internship->company->profile->name ?? '-',
                'school' => $internship->school->name ?? 'Semua Sekolah',
                'major' => $internship->major->name ?? 'Semua Jurusan',
                'status' => $internship->status,
                'quota' => $internship->quota,
                'applications' => $applicationCount,
                'accepted' => $acceptedCount,
                'period_start' => $internship->period_start?->format('Y-m-d'),
                'period_end' => $internship->period_end?->format('Y-m-d'),
            ];
        });

        $summary = [
            'total' => $internships->count(),
            'published' => $internships->filter(fn ($i) => $i['status'] === 'PUBLISHED')->count(),
            'draft' => $internships->filter(fn ($i) => $i['status'] === 'DRAFT')->count(),
            'closed' => $internships->filter(fn ($i) => $i['status'] === 'CLOSED')->count(),
        ];

        return $this->success([
            'summary' => $summary,
            'internships' => $internships,
        ], 'Laporan lowongan berhasil diambil.');
    }

    /**
     * GET /api/admin/reports/applications — Laporan lamaran.
     */
    public function applications(Request $request): JsonResponse
    {
        $query = Application::with([
            'student:id,user_id',
            'student.user:id,name',
            'student.school:id,name',
            'student.major:id,name',
            'internship:id,title',
            'internship.company:id',
            'internship.company.profile:company_id,name',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('school_id')) {
            $query->whereHas('student', fn ($q) => $q->where('school_id', $request->input('school_id')));
        }

        if ($request->filled('company_id')) {
            $query->whereHas('internship', fn ($q) => $q->where('company_id', $request->input('company_id')));
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

        $bySchool = $applications->groupBy(fn ($a) => $a->student->school->name ?? '-')->map(function ($group, $schoolName) {
            return [
                'school' => $schoolName,
                'total' => $group->count(),
                'accepted' => $group->filter(fn ($a) => $a->status === Application::STATUS_ACCEPTED)->count(),
                'rejected' => $group->filter(fn ($a) => $a->status === Application::STATUS_REJECTED)->count(),
            ];
        })->values();

        $byCompany = $applications->groupBy(fn ($a) => $a->internship->company->profile->name ?? '-')->map(function ($group, $companyName) {
            return [
                'company' => $companyName,
                'total' => $group->count(),
                'accepted' => $group->filter(fn ($a) => $a->status === Application::STATUS_ACCEPTED)->count(),
                'rejected' => $group->filter(fn ($a) => $a->status === Application::STATUS_REJECTED)->count(),
            ];
        })->values();

        return $this->success([
            'summary' => $summary,
            'by_school' => $bySchool,
            'by_company' => $byCompany,
        ], 'Laporan lamaran berhasil diambil.');
    }
}
