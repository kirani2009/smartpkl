<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\SchoolCompanyPartnership;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 5 — Company.
 * Dashboard perusahaan: ringkasan data perusahaan.
 * Hanya perusahaan (ROLES.json: company mengelola profil, partnership, dan internship).
 */
class CompanyDashboardController extends Controller
{
    use ApiResponseTrait;

    public function show(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat. Silakan lengkapi profil terlebih dahulu.', null, 404);
        }

        $companyId = $company->id;

        // Partnership stats
        $partnershipsTotal = SchoolCompanyPartnership::where('company_id', $companyId)->count();
        $partnershipsActive = SchoolCompanyPartnership::where('company_id', $companyId)
            ->where('status', SchoolCompanyPartnership::STATUS_ACCEPTED)->count();
        $partnershipsPending = SchoolCompanyPartnership::where('company_id', $companyId)
            ->where('status', SchoolCompanyPartnership::STATUS_PENDING)->count();

        // Internship stats
        $internshipListings = $company->internshipListings()
            ->selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        // Application stats
        $applicationStats = Application::query()
            ->whereHas('internship', fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw("status, count(*) as total")
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalApplicants = $applicationStats->sum();

        // Recent applications
        $recentApplications = Application::query()
            ->with(['student.user:id,name', 'internship:id,title'])
            ->whereHas('internship', fn ($q) => $q->where('company_id', $companyId))
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
            'company' => [
                'id' => $company->id,
                'name' => $company->profile?->name ?? '-',
                'status' => $company->status,
            ],
            'partnerships' => [
                'total' => $partnershipsTotal,
                'active' => $partnershipsActive,
                'pending' => $partnershipsPending,
            ],
            'internship_listings' => [
                'total' => $internshipListings->sum(),
                'draft' => $internshipListings->get('DRAFT', 0),
                'published' => $internshipListings->get('PUBLISHED', 0),
                'closed' => $internshipListings->get('CLOSED', 0),
            ],
            'applicants' => [
                'total' => $totalApplicants,
                'pending' => $applicationStats->get('PENDING', 0),
                'reviewed' => $applicationStats->get('REVIEWED', 0),
                'interview' => $applicationStats->get('INTERVIEW', 0),
                'accepted' => $applicationStats->get('ACCEPTED', 0),
                'rejected' => $applicationStats->get('REJECTED', 0),
            ],
            'recent_applications' => $recentApplications,
        ], 'Dashboard perusahaan berhasil diambil.');
    }
}
