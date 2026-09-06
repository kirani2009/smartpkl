<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\InternshipResource;
use App\Models\Application;
use App\Models\ApplicationAttachment;
use App\Models\ApplicationStatusHistory;
use App\Models\InternshipListing;
use App\Models\Notification;
use App\Models\SavedInternship;
use App\Models\SchoolCompanyPartnership;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * PHASE 9 — Application.
 * Siswa menjelajahi lowongan, melamar, melihat lamaran, dan menyimpan lowongan.
 */
class StudentInternshipController extends Controller
{
    use ApiResponseTrait;

    // ---- Browse Internships ----

    /**
     * GET /api/student/internships — daftar lowongan PUBLISHED yang bisa dilihat siswa.
     * Lowongan spesifik sekolah hanya muncul jika sekolah siswa punya partnership ACTIVE.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $internships = InternshipListing::query()
            ->with(['company:id,user_id', 'company.user:id,name', 'school:id,name', 'major:id,name', 'requirements', 'skills'])
            ->where('status', InternshipListing::STATUS_PUBLISHED)
            ->where(function ($query) use ($student) {
                // Lowongan umum (school_id null) atau lowongan khusus sekolah dengan partnership ACTIVE
                $query->whereNull('school_id')
                    ->orWhere(function ($q) use ($student) {
                        $q->where('school_id', $student->school_id)
                            ->whereIn('school_id', function ($subQ) use ($student) {
                                $subQ->select('school_id')
                                    ->from('school_company_partnerships')
                                    ->where('company_id', \DB::raw('internship_listings.company_id'))
                                    ->where('status', SchoolCompanyPartnership::STATUS_ACCEPTED);
                            });
                    });
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim($request->input('q'));
                $query->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhere('position', 'like', "%{$q}%")
                        ->orWhere('required_major', 'like', "%{$q}%")
                        ->orWhereHas('major', fn ($mq) => $mq->where('name', 'like', "%{$q}%"))
                        ->orWhereHas('company.user', fn ($cq) => $cq->where('name', 'like', "%{$q}%"));
                });
            })
            ->withCount('applications')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => InternshipResource::collection($internships),
            'meta' => [
                'current_page' => $internships->currentPage(),
                'last_page' => $internships->lastPage(),
                'per_page' => $internships->perPage(),
                'total' => $internships->total(),
            ],
        ], 'Daftar lowongan berhasil diambil.');
    }

    /**
     * GET /api/student/internships/filters — opsi filter (companies, majors, positions).
     */
    public function filters(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        // Ambil perusahaan unik dari lowongan PUBLISHED
        $companies = InternshipListing::query()
            ->select('company_id')
            ->distinct()
            ->where('status', InternshipListing::STATUS_PUBLISHED)
            ->where(function ($query) use ($student) {
                $query->whereNull('school_id')
                    ->orWhere(function ($q) use ($student) {
                        $q->where('school_id', $student->school_id)
                            ->whereIn('school_id', function ($subQ) use ($student) {
                                $subQ->select('school_id')
                                    ->from('school_company_partnerships')
                                    ->where('company_id', \DB::raw('internship_listings.company_id'))
                                    ->where('status', SchoolCompanyPartnership::STATUS_ACCEPTED);
                            });
                    });
            })
            ->with('company.user:id,name')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->company->id,
                'name' => $item->company->user->name ?? 'Perusahaan',
            ])
            ->unique('id')
            ->values();

        // Ambil jurusan unik dari lowongan PUBLISHED
        $majors = InternshipListing::query()
            ->select('major_id')
            ->distinct()
            ->where('status', InternshipListing::STATUS_PUBLISHED)
            ->whereNotNull('major_id')
            ->where(function ($query) use ($student) {
                $query->whereNull('school_id')
                    ->orWhere(function ($q) use ($student) {
                        $q->where('school_id', $student->school_id)
                            ->whereIn('school_id', function ($subQ) use ($student) {
                                $subQ->select('school_id')
                                    ->from('school_company_partnerships')
                                    ->where('company_id', \DB::raw('internship_listings.company_id'))
                                    ->where('status', SchoolCompanyPartnership::STATUS_ACCEPTED);
                            });
                    });
            })
            ->with('major:id,name')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->major->id,
                'name' => $item->major->name,
            ])
            ->unique('id')
            ->values();

        // Ambil posisi unik dari lowongan PUBLISHED
        $positions = InternshipListing::query()
            ->select('position')
            ->distinct()
            ->where('status', InternshipListing::STATUS_PUBLISHED)
            ->whereNotNull('position')
            ->where(function ($query) use ($student) {
                $query->whereNull('school_id')
                    ->orWhere(function ($q) use ($student) {
                        $q->where('school_id', $student->school_id)
                            ->whereIn('school_id', function ($subQ) use ($student) {
                                $subQ->select('school_id')
                                    ->from('school_company_partnerships')
                                    ->where('company_id', \DB::raw('internship_listings.company_id'))
                                    ->where('status', SchoolCompanyPartnership::STATUS_ACCEPTED);
                            });
                    });
            })
            ->pluck('position')
            ->filter()
            ->unique()
            ->values();

        return $this->success([
            'companies' => $companies,
            'majors' => $majors,
            'positions' => $positions,
        ], 'Opsi filter berhasil diambil.');
    }

    /**
     * GET /api/student/internships/{internship} — detail lowongan.
     */
    public function show(Request $request, InternshipListing $internship): JsonResponse
    {
        if ($internship->status !== InternshipListing::STATUS_PUBLISHED) {
            return $this->error('Lowongan tidak tersedia.', null, 404);
        }

        $internship->load(['school', 'major', 'requirements', 'skills', 'company.profile']);
        $internship->loadCount('applications');

        return $this->success(new InternshipResource($internship), 'Detail lowongan berhasil diambil.');
    }

    // ---- Apply ----

    /**
     * POST /api/student/internships/{internship}/apply — melamar ke lowongan.
     */
    public function apply(Request $request, InternshipListing $internship): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        if ($internship->status !== InternshipListing::STATUS_PUBLISHED) {
            return $this->error('Lowongan tidak tersedia untuk dilamar.', null, 422);
        }

        // Cek apakah sudah melamar
        $existingApplication = Application::where('internship_id', $internship->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existingApplication) {
            return $this->error('Anda sudah melamar ke lowongan ini.', null, 409);
        }

        $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => Application::STATUS_PENDING,
            'message' => $request->input('message'),
            'applied_at' => now(),
        ]);

        // Handle file attachments
        $allowedTypes = ['CV', 'IJAZAH', 'PORTFOLIO', 'OTHER'];
        $attachmentFields = ['cv', 'ijazah', 'portfolio'];

        foreach ($attachmentFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $type = strtoupper($field);
                $originalName = $file->getClientOriginalName();
                $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('applications/' . $application->id, $fileName, 'public');

                $application->attachments()->create([
                    'type' => $type,
                    'title' => $type . ' - ' . $originalName,
                    'file_path' => $filePath,
                    'original_name' => $originalName,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        // Handle generic file attachment
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $originalName = $file->getClientOriginalName();
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('applications/' . $application->id, $fileName, 'public');
            $type = strtoupper($request->input('attachment_type', 'OTHER'));

            if (! in_array($type, $allowedTypes)) {
                $type = 'OTHER';
            }

            $application->attachments()->create([
                'type' => $type,
                'title' => $request->input('attachment_title', $type . ' - ' . $originalName),
                'file_path' => $filePath,
                'original_name' => $originalName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);
        }

        // Catat di status history
        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'status' => Application::STATUS_PENDING,
            'changed_by' => $request->user()->id,
            'note' => 'Lamaran dikirim.',
        ]);

        // Kirim notifikasi ke perusahaan
        $company = $internship->company;
        $companyUser = $company->user;
        $studentName = $request->user()->name;

        Notification::create([
            'user_id' => $companyUser->id,
            'type' => 'NEW_APPLICATION',
            'title' => 'Lamaran Baru Diterima',
            'message' => "{$studentName} telah melamar ke lowongan {$internship->title}.",
            'data' => [
                'application_id' => $application->id,
                'student_name' => $studentName,
                'internship_title' => $internship->title,
            ],
        ]);

        return $this->success(new ApplicationResource($application->load(['internship', 'student', 'attachments'])), 'Lamaran berhasil dikirim.', 201);
    }

    // ---- My Applications ----

    /**
     * GET /api/student/applications — daftar lamaran siswa.
     */
    public function myApplications(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $applications = Application::query()
            ->with(['internship.company.profile', 'internship.school', 'statusHistories.changedBy', 'attachments'])
            ->where('student_id', $student->id)
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
        ], 'Daftar lamaran berhasil diambil.');
    }

    /**
     * GET /api/student/applications/{application} — detail lamaran.
     */
    public function showApplication(Request $request, Application $application): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $application->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke lamaran ini.', null, 403);
        }

        $application->load(['internship.company.profile', 'internship.school', 'internship.major', 'internship.requirements', 'statusHistories.changedBy', 'interviews', 'attachments']);

        return $this->success(new ApplicationResource($application), 'Detail lamaran berhasil diambil.');
    }

    // ---- Cancel Application ----

    /**
     * DELETE /api/student/applications/{application} — batalkan lamaran.
     * Hanya bisa dibatalkan jika status masih PENDING.
     */
    public function cancel(Request $request, Application $application): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student || $application->student_id !== $student->id) {
            return $this->error('Anda tidak memiliki akses ke lamaran ini.', null, 403);
        }

        if ($application->status !== Application::STATUS_PENDING) {
            return $this->error('Lamaran dengan status ' . $application->status . ' tidak dapat dibatalkan.', null, 422);
        }

        $application->update(['status' => 'CANCELLED']);

        ApplicationStatusHistory::create([
            'application_id' => $application->id,
            'status' => 'CANCELLED',
            'changed_by' => $request->user()->id,
            'note' => 'Lamaran dibatalkan oleh siswa.',
        ]);

        return $this->success(new ApplicationResource($application->fresh(['internship', 'attachments'])), 'Lamaran berhasil dibatalkan.');
    }

    // ---- Save / Unsave Internship ----

    /**
     * POST /api/student/internships/{internship}/save — simpan lowongan.
     */
    public function save(Request $request, InternshipListing $internship): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $exists = SavedInternship::where('student_id', $student->id)
            ->where('internship_id', $internship->id)
            ->exists();

        if ($exists) {
            return $this->error('Lowongan sudah disimpan.', null, 409);
        }

        SavedInternship::create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
        ]);

        return $this->success(null, 'Lowongan berhasil disimpan.', 201);
    }

    /**
     * DELETE /api/student/internships/{internship}/save — hapus simpanan lowongan.
     */
    public function unsave(Request $request, InternshipListing $internship): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $deleted = SavedInternship::where('student_id', $student->id)
            ->where('internship_id', $internship->id)
            ->delete();

        if ($deleted === 0) {
            return $this->error('Lowongan tidak ditemukan di daftar simpanan.', null, 404);
        }

        return $this->success(null, 'Lowongan berhasil dihapus dari daftar simpanan.');
    }

    /**
     * GET /api/student/saved-internships — daftar lowongan yang disimpan.
     */
    public function savedInternships(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (! $student) {
            return $this->error('Profil siswa belum dibuat.', null, 404);
        }

        $saved = SavedInternship::query()
            ->with(['internship.company.profile', 'internship.school', 'internship.major'])
            ->where('student_id', $student->id)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => $saved,
            'meta' => [
                'current_page' => $saved->currentPage(),
                'last_page' => $saved->lastPage(),
                'per_page' => $saved->perPage(),
                'total' => $saved->total(),
            ],
        ], 'Daftar lowongan tersimpan berhasil diambil.');
    }
}
