<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\InterviewResource;
use App\Models\Application;
use App\Models\Interview;
use App\Traits\ApiResponseTrait;
use App\Traits\HasCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 11 — Interview.
 * Company mengelola interview: daftar, lihat, selesai, batalkan.
 */
class CompanyInterviewController extends Controller
{
    use ApiResponseTrait, HasCompany;

    /**
     * GET /api/company/interviews — daftar interview untuk lowongan perusahaan ini.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }

        $interviews = Interview::query()
            ->with([
                'application.student.user:id,name',
                'application.student.school:id,name',
                'application.internship:id,title',
            ])
            ->whereHas('application.internship', fn ($q) => $q->where('company_id', $company->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('scheduled_at', $request->input('date')))
            ->orderBy('scheduled_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => InterviewResource::collection($interviews),
            'meta' => [
                'current_page' => $interviews->currentPage(),
                'last_page' => $interviews->lastPage(),
                'per_page' => $interviews->perPage(),
                'total' => $interviews->total(),
            ],
        ], 'Daftar interview berhasil diambil.');
    }

    /**
     * GET /api/company/interviews/{interview} — detail interview.
     */
    public function show(Request $request, Interview $interview): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $interview->application->internship->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke interview ini.', null, 403);
        }

        $interview->load([
            'application.student.user',
            'application.student.school',
            'application.student.major',
            'application.student.skills',
            'application.student.portfolios',
            'application.student.documents',
            'application.internship',
        ]);

        return $this->success(new InterviewResource($interview), 'Detail interview berhasil diambil.');
    }

    /**
     * PUT /api/company/interviews/{interview}/complete — tandai interview selesai.
     */
    public function complete(Request $request, Interview $interview): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $interview->application->internship->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke interview ini.', null, 403);
        }

        if ($interview->status !== Interview::STATUS_SCHEDULED) {
            return $this->error('Hanya interview dengan status SCHEDULED yang bisa diselesaikan.', null, 422);
        }

        $interview->update(['status' => Interview::STATUS_COMPLETED]);

        return $this->success(new InterviewResource($interview->fresh()), 'Interview berhasil ditandai selesai.');
    }

    /**
     * PUT /api/company/interviews/{interview}/cancel — batalkan interview.
     */
    public function cancel(Request $request, Interview $interview): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $interview->application->internship->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke interview ini.', null, 403);
        }

        if ($interview->status !== Interview::STATUS_SCHEDULED) {
            return $this->error('Hanya interview dengan status SCHEDULED yang bisa dibatalkan.', null, 422);
        }

        $interview->update(['status' => Interview::STATUS_CANCELLED]);

        // Kembalikan status lamaran ke REVIEWED
        $application = $interview->application;
        if ($application->status === Application::STATUS_INTERVIEW) {
            $application->update(['status' => Application::STATUS_REVIEWED]);

            \App\Models\ApplicationStatusHistory::create([
                'application_id' => $application->id,
                'status' => Application::STATUS_REVIEWED,
                'changed_by' => $request->user()->id,
                'note' => 'Interview dibatalkan, status dikembalikan ke REVIEWED.',
            ]);
        }

        return $this->success(new InterviewResource($interview->fresh()), 'Interview berhasil dibatalkan.');
    }
}
