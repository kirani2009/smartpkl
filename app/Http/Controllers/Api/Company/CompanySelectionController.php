<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\InternshipListing;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PHASE 10 — Selection.
 * Fitur lanjutan seleksi: statistik per lowongan, operasi batch, rating.
 */
class CompanySelectionController extends Controller
{
    use ApiResponseTrait;

    /**
     * GET /api/company/selection/stats — statistik seleksi per lowongan.
     */
    public function stats(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }

        $internships = InternshipListing::query()
            ->withCount([
                'applications as total_applications',
                'applications as pending_applications' => fn ($q) => $q->where('status', 'PENDING'),
                'applications as reviewed_applications' => fn ($q) => $q->where('status', 'REVIEWED'),
                'applications as interview_applications' => fn ($q) => $q->where('status', 'INTERVIEW'),
                'applications as accepted_applications' => fn ($q) => $q->where('status', 'ACCEPTED'),
                'applications as rejected_applications' => fn ($q) => $q->where('status', 'REJECTED'),
            ])
            ->where('company_id', $company->id)
            ->where('status', '!=', 'DRAFT')
            ->latest()
            ->get()
            ->map(fn (InternshipListing $internship) => [
                'id' => $internship->id,
                'title' => $internship->title,
                'quota' => $internship->quota,
                'status' => $internship->status,
                'applications' => [
                    'total' => $internship->total_applications,
                    'pending' => $internship->pending_applications,
                    'reviewed' => $internship->reviewed_applications,
                    'interview' => $internship->interview_applications,
                    'accepted' => $internship->accepted_applications,
                    'rejected' => $internship->rejected_applications,
                ],
                'acceptance_rate' => $internship->total_applications > 0
                    ? round($internship->accepted_applications / $internship->total_applications * 100, 1)
                    : 0,
            ]);

        return $this->success([
            'items' => $internships,
        ], 'Statistik seleksi berhasil diambil.');
    }

    /**
     * PUT /api/company/applications/{application}/rate — beri rating pada pelamar.
     */
    public function rate(Request $request, Application $application): JsonResponse
    {
        $company = $request->user()->company;

        if (! $company || $application->internship->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke lamaran ini.', null, 403);
        }

        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'selection_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $application->update([
            'rating' => $request->input('rating'),
            'selection_notes' => $request->input('selection_notes'),
        ]);

        return $this->success(new ApplicationResource($application->fresh(['internship'])), 'Rating berhasil diperbarui.');
    }

    /**
     * PUT /api/company/selection/batch — batch update status beberapa lamaran.
     * Body: { "application_ids": [1,2,3], "status": "REJECTED", "note": "..." }
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }

        $request->validate([
            'application_ids' => ['required', 'array', 'min:1', 'max:50'],
            'application_ids.*' => ['required', 'exists:applications,id'],
            'status' => ['required', 'in:REVIEWED,INTERVIEW,ACCEPTED,REJECTED'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $applicationIds = $request->input('application_ids');
        $newStatus = $request->input('status');
        $note = $request->input('note');

        // Validasi transisi status
        $validTransitions = [
            Application::STATUS_PENDING => ['REVIEWED', 'ACCEPTED', 'REJECTED'],
            Application::STATUS_REVIEWED => ['INTERVIEW', 'ACCEPTED', 'REJECTED'],
            Application::STATUS_INTERVIEW => ['ACCEPTED', 'REJECTED'],
        ];

        $applications = Application::query()
            ->whereIn('id', $applicationIds)
            ->with('internship')
            ->get()
            ->filter(fn ($app) => $app->internship->company_id === $company->id);

        $updated = 0;
        $skipped = 0;
        $errors = [];
        $userId = $request->user()->id;

        foreach ($applications as $application) {
            $oldStatus = $application->status;

            if (! isset($validTransitions[$oldStatus]) || ! in_array($newStatus, $validTransitions[$oldStatus], true)) {
                $skipped++;
                $errors[] = "Application #{$application->id}: transisi {$oldStatus} -> {$newStatus} tidak valid.";
                continue;
            }

            DB::transaction(function () use ($application, $newStatus, $note, $userId) {
                $application->update(['status' => $newStatus]);

                ApplicationStatusHistory::create([
                    'application_id' => $application->id,
                    'status' => $newStatus,
                    'changed_by' => $userId,
                    'note' => $note,
                ]);
            });

            $updated++;
        }

        return $this->success([
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ], "{$updated} lamaran berhasil diperbarui.");
    }

    /**
     * GET /api/company/selection/top-applicants — pelamar terbaik (berdasarkan rating).
     */
    public function topApplicants(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }

        $applications = Application::query()
            ->with(['student.user:id,name', 'student.school:id,name', 'internship:id,title'])
            ->whereHas('internship', fn ($q) => $q->where('company_id', $company->id))
            ->whereNotNull('rating')
            ->orderByDesc('rating')
            ->orderByDesc('applied_at')
            ->limit($request->integer('per_page', 20))
            ->get();

        return $this->success([
            'items' => ApplicationResource::collection($applications),
        ], 'Daftar pelamar terbaik berhasil diambil.');
    }
}
