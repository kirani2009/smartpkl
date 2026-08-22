<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Interview;
use App\Models\Notification;
use App\Models\SchoolCompanyPartnership;
use App\Models\User;

/**
 * PHASE 13 — Notification.
 * Service untuk membuat notifikasi dari berbagai event.
 * Shared hosting friendly: notifikasi disimpan di database.
 */
class NotificationService
{
    /**
     * Buat notifikasi baru.
     */
    public static function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?array $data = null,
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    // ---- Partnership Notifications ----

    /**
     * Company menerima partnership request dari guru.
     */
    public static function partnershipRequested(SchoolCompanyPartnership $partnership): void
    {
        $company = $partnership->company;
        if (! $company) return;

        $schoolName = $partnership->school?->name ?? 'Sekolah';
        $requesterName = $partnership->requester?->name ?? 'Guru';

        self::create(
            $company->user_id,
            'PARTNERSHIP_REQUESTED',
            'Permintaan Partnership Baru',
            "{$requesterName} dari {$schoolName} mengajukan permintaan partnership.",
            ['partnership_id' => $partnership->id, 'school_name' => $schoolName]
        );
    }

    /**
     * Guru menerima/tolak partnership.
     */
    public static function partnershipResponded(SchoolCompanyPartnership $partnership): void
    {
        $requesterId = $partnership->requested_by;
        if (! $requesterId) return;

        $companyName = $partnership->company?->profile?->name ?? 'Perusahaan';
        $status = $partnership->status === SchoolCompanyPartnership::STATUS_ACCEPTED ? 'diterima' : 'ditolak';

        self::create(
            $requesterId,
            'PARTNERSHIP_RESPONDED',
            "Partnership {$status}",
            "Permintaan partnership dengan {$companyName} telah {$status}.",
            ['partnership_id' => $partnership->id, 'status' => $partnership->status]
        );
    }

    // ---- Application Notifications ----

    /**
     * Company menerima lamaran baru dari siswa.
     */
    public static function applicationReceived(Application $application): void
    {
        $company = $application->internship?->company;
        if (! $company) return;

        $studentName = $application->student?->user?->name ?? 'Siswa';
        $internshipTitle = $application->internship?->title ?? 'Lowongan';

        self::create(
            $company->user_id,
            'APPLICATION_RECEIVED',
            'Lamaran Baru Diterima',
            "{$studentName} melamar ke lowongan \"{$internshipTitle}\".",
            ['application_id' => $application->id, 'student_name' => $studentName]
        );
    }

    /**
     * Siswa menerima update status lamaran.
     */
    public static function applicationStatusChanged(Application $application, string $note = null): void
    {
        $studentId = $application->student?->user_id;
        if (! $studentId) return;

        $companyName = $application->internship?->company?->profile?->name ?? 'Perusahaan';
        $internshipTitle = $application->internship?->title ?? 'Lowongan';
        $statusLabels = [
            'REVIEWED' => 'sedang ditinjau',
            'INTERVIEW' => 'diundang interview',
            'ACCEPTED' => 'diterima',
            'REJECTED' => 'tidak diterima',
        ];
        $statusLabel = $statusLabels[$application->status] ?? strtolower($application->status);

        self::create(
            $studentId,
            'APPLICATION_STATUS_CHANGED',
            'Status Lamaran Diperbarui',
            "Lamaran Anda ke \"{$internshipTitle}\" di {$companyName} {$statusLabel}.",
            array_merge(
                ['application_id' => $application->id, 'status' => $application->status],
                $note ? ['note' => $note] : []
            )
        );
    }

    // ---- Interview Notifications ----

    /**
     * Siswa diundang interview.
     */
    public static function interviewScheduled(Interview $interview): void
    {
        $application = $interview->application;
        $studentId = $application?->student?->user_id;
        if (! $studentId) return;

        $companyName = $application->internship?->company?->profile?->name ?? 'Perusahaan';
        $mode = $interview->mode === 'online' ? 'Online' : 'Onsite';
        $location = $interview->location ?? '';
        $scheduledAt = $interview->scheduled_at?->format('d M Y, H:i') ?? '';

        self::create(
            $studentId,
            'INTERVIEW_SCHEDULED',
            'Interview Dijadwalkan',
            "Anda diundang interview {$mode} di {$companyName} pada {$scheduledAt}.",
            ['interview_id' => $interview->id, 'company_name' => $companyName]
        );
    }

    /**
     * Siswa: interview dibatalkan.
     */
    public static function interviewCancelled(Interview $interview): void
    {
        $application = $interview->application;
        $studentId = $application?->student?->user_id;
        if (! $studentId) return;

        $companyName = $application->internship?->company?->profile?->name ?? 'Perusahaan';

        self::create(
            $studentId,
            'INTERVIEW_CANCELLED',
            'Interview Dibatalkan',
            "Interview Anda di {$companyName} telah dibatalkan.",
            ['interview_id' => $interview->id]
        );
    }
}
