<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipListing;
use App\Models\SchoolCompanyPartnership;
use App\Traits\ApiResponseTrait;
use App\Traits\HasCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 15 — Reporting.
 * Laporan perusahaan: hanya data perusahaan sendiri (docs/ai/REPORTING.json).
 */
class CompanyReportController extends Controller
{
    use ApiResponseTrait, HasCompany;

    /**
     * GET /api/company/reports/applications — Laporan lamaran perusahaan.
     */
    public function applications(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }
        $companyId = $company->id;

        $query = Application::with([
            'student:id,user_id,school_id,major_id',
            'student.user:id,name',
            'student.school:id,name',
            'student.major:id,name',
            'internship:id,title',
        ])->whereHas('internship', fn ($q) => $q->where('company_id', $companyId));

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('internship_id')) {
            $query->where('internship_id', $request->input('internship_id'));
        }

        if ($request->filled('school_id')) {
            $query->whereHas('student', fn ($q) => $q->where('school_id', $request->input('school_id')));
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

        $byInternship = $applications->groupBy(fn ($a) => $a->internship->title ?? '-')->map(function ($group, $title) {
            return [
                'internship' => $title,
                'total' => $group->count(),
                'accepted' => $group->filter(fn ($a) => $a->status === Application::STATUS_ACCEPTED)->count(),
                'rejected' => $group->filter(fn ($a) => $a->status === Application::STATUS_REJECTED)->count(),
            ];
        })->values();

        $bySchool = $applications->groupBy(fn ($a) => $a->student->school->name ?? '-')->map(function ($group, $schoolName) {
            return [
                'school' => $schoolName,
                'total' => $group->count(),
                'accepted' => $group->filter(fn ($a) => $a->status === Application::STATUS_ACCEPTED)->count(),
                'rejected' => $group->filter(fn ($a) => $a->status === Application::STATUS_REJECTED)->count(),
            ];
        })->values();

        return $this->success([
            'summary' => $summary,
            'by_internship' => $byInternship,
            'by_school' => $bySchool,
        ], 'Laporan lamaran berhasil diambil.');
    }

    /**
     * GET /api/company/reports/internships — Laporan lowongan perusahaan.
     */
    public function internships(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }
        $companyId = $company->id;

        $internships = InternshipListing::with(['school:id,name', 'major:id,name'])
            ->where('company_id', $companyId)
            ->get()
            ->map(function ($internship) {
                $applicationCount = $internship->applications()->count();
                $acceptedCount = $internship->applications()->where('status', Application::STATUS_ACCEPTED)->count();

                return [
                    'id' => $internship->id,
                    'title' => $internship->title,
                    'status' => $internship->status,
                    'school' => $internship->school->name ?? 'Semua Sekolah',
                    'major' => $internship->major->name ?? 'Semua Jurusan',
                    'quota' => $internship->quota,
                    'filled' => $acceptedCount,
                    'remaining' => max(0, $internship->quota - $acceptedCount),
                    'applications' => $applicationCount,
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
     * GET /api/company/reports/partnerships — Laporan partnership perusahaan.
     */
    public function partnerships(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }
        $companyId = $company->id;

        $partnerships = SchoolCompanyPartnership::with(['school:id,name'])
            ->where('company_id', $companyId)
            ->get();

        $summary = [
            'total' => $partnerships->count(),
            'active' => $partnerships->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_ACCEPTED)->count(),
            'pending' => $partnerships->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_PENDING)->count(),
            'rejected' => $partnerships->filter(fn ($p) => $p->status === SchoolCompanyPartnership::STATUS_REJECTED)->count(),
        ];

        $bySchool = $partnerships->groupBy(fn ($p) => $p->school->name ?? '-')->map(function ($group, $schoolName) {
            return [
                'school' => $schoolName,
                'total' => $group->count(),
                'status' => $group->first()->status,
            ];
        })->values();

        return $this->success([
            'summary' => $summary,
            'partnerships' => $bySchool,
        ], 'Laporan partnership berhasil diambil.');
    }
}
