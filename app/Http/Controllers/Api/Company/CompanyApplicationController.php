<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\InterviewResource;
use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Interview;
use App\Traits\ApiResponseTrait;
use App\Traits\HasCompany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PHASE 9 — Application.
 * Company mengelola pelamar: melihat, update status, jadwalkan interview.
 */
class CompanyApplicationController extends Controller
{
    use ApiResponseTrait, HasCompany;

    /**
     * GET /api/company/applications — daftar semua pelamar untuk lowongan perusahaan ini.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 404);
        }

        $applications = Application::query()
            ->with(['student.user:id,name', 'student.school:id,name', 'internship:id,title'])
            ->whereHas('internship', fn ($q) => $q->where('company_id', $company->id))
            ->when($request->filled('internship_id'), fn ($q) => $q->where('internship_id', $request->input('internship_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('applied_at')
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => ApplicationResource::collection($applications),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ], 'Daftar pelamar berhasil diambil.');
    }

    /**
     * GET /api/company/applications/{application} — detail pelamar.
     */
    public function show(Request $request, Application $application): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company) {
            return $this->error('Profil perusahaan belum dibuat.', null, 403);
        }

        // Pastikan lamaran ini untuk lowongan milik perusahaan
        $ownsInternship = $application->internship->company_id === $company->id;
        if (! $ownsInternship) {
            return $this->error('Anda tidak memiliki akses ke lamaran ini.', null, 403);
        }

        $application->load([
            'student.user', 'student.school', 'student.major', 'student.skills',
            'student.portfolios', 'student.documents', 'student.certificates',
            'internship', 'statusHistories.changedBy', 'interviews',
        ]);

        return $this->success(new ApplicationResource($application), 'Detail pelamar berhasil diambil.');
    }

    /**
     * PUT /api/company/applications/{application}/status — update status lamaran.
     * Status yang diizinkan: REVIEWED, INTERVIEW, ACCEPTED, REJECTED.
     */
    public function updateStatus(Request $request, Application $application): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $application->internship->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke lamaran ini.', null, 403);
        }

        $request->validate([
            'status' => ['required', 'in:REVIEWED,INTERVIEW,ACCEPTED,REJECTED'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = $request->input('status');
        $oldStatus = $application->status;

        // Validasi transisi status
        $validTransitions = [
            Application::STATUS_PENDING => ['REVIEWED', 'ACCEPTED', 'REJECTED'],
            Application::STATUS_REVIEWED => ['INTERVIEW', 'ACCEPTED', 'REJECTED'],
            Application::STATUS_INTERVIEW => ['ACCEPTED', 'REJECTED'],
        ];

        if (! isset($validTransitions[$oldStatus]) || ! in_array($newStatus, $validTransitions[$oldStatus], true)) {
            return $this->error("Tidak bisa mengubah status dari {$oldStatus} ke {$newStatus}.", null, 422);
        }

        $application->update(['status' => $newStatus]);

        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'status' => $newStatus,
            'changed_by' => $request->user()->id,
            'note' => $request->input('note'),
        ]);

        return $this->success(new ApplicationResource($application->fresh(['internship'])), 'Status lamaran berhasil diperbarui.');
    }

    /**
     * POST /api/company/applications/{application}/interview — jadwalkan interview.
     */
    public function scheduleInterview(Request $request, Application $application): JsonResponse
    {
        $company = $this->resolveCompany($request);

        if (! $company || $application->internship->company_id !== $company->id) {
            return $this->error('Anda tidak memiliki akses ke lamaran ini.', null, 403);
        }

        // Cek sudah ada interview aktif
        $hasActiveInterview = Interview::where('application_id', $application->id)
            ->where('status', Interview::STATUS_SCHEDULED)
            ->exists();

        if ($hasActiveInterview) {
            return $this->error('Sudah ada interview aktif untuk lamaran ini.', null, 409);
        }

        // Hanya bisa interview jika status REVIEWED
        if ($application->status !== Application::STATUS_REVIEWED) {
            return $this->error('Hanya lamaran dengan status REVIEWED yang bisa dijadwalkan interview.', null, 422);
        }

        $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
            'mode' => ['required', 'in:onsite,online'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $interview = Interview::create([
            'application_id' => $application->id,
            'scheduled_at' => $request->input('scheduled_at'),
            'mode' => $request->input('mode'),
            'location' => $request->input('location'),
            'notes' => $request->input('notes'),
            'status' => Interview::STATUS_SCHEDULED,
        ]);

        // Update status lamaran ke INTERVIEW
        $application->update(['status' => Application::STATUS_INTERVIEW]);

        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'status' => Application::STATUS_INTERVIEW,
            'changed_by' => $request->user()->id,
            'note' => 'Interview dijadwalkan.',
        ]);

        return $this->success(new InterviewResource($interview), 'Interview berhasil dijadwalkan.', 201);
    }
}
