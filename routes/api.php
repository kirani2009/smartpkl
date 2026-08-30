<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Company\CompanyApplicationController;
use App\Http\Controllers\Api\Company\CompanyDashboardController;
use App\Http\Controllers\Api\Company\CompanyInternshipController;
use App\Http\Controllers\Api\Company\CompanyInterviewController;
use App\Http\Controllers\Api\Company\CompanyPartnershipController;
use App\Http\Controllers\Api\Company\CompanyProfileController;
use App\Http\Controllers\Api\Admin\AdminReportController;
use App\Http\Controllers\Api\Company\CompanyReportController;
use App\Http\Controllers\Api\Company\CompanySelectionController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\School\MajorController;
use App\Http\Controllers\Api\School\SchoolController;

use App\Http\Controllers\Api\Student\StudentCertificateController;
use App\Http\Controllers\Api\Student\StudentDocumentController;
use App\Http\Controllers\Api\Student\StudentInterviewController;
use App\Http\Controllers\Api\Student\StudentInternshipController;
use App\Http\Controllers\Api\Student\StudentPortfolioController;
use App\Http\Controllers\Api\Student\StudentProfileController;
use App\Http\Controllers\Api\Student\StudentSkillController;
use App\Http\Controllers\Api\Student\SmartMatchingController;
use App\Http\Controllers\Api\Teacher\TeacherApplicationController;
use App\Http\Controllers\Api\Teacher\TeacherDashboardController;
use App\Http\Controllers\Api\Teacher\TeacherPartnershipController;
use App\Http\Controllers\Api\Teacher\TeacherMonitoringController;
use App\Http\Controllers\Api\Teacher\TeacherProfileController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Base URL: /api
| Authentication: Laravel Sanctum (Bearer token)
| Response structure: { success, message, data, errors }
|
| PHASE 2 — Authentication & Roles (docs/ai/AUTH.json):
|   POST /api/auth/register  -> registrasi user baru
|   POST /api/auth/login     -> login (rate limited)
|   POST /api/auth/logout    -> logout (auth)
|   GET  /api/me             -> profil user saat ini (auth)
|   PUT  /api/me             -> perbarui profil sendiri (auth)
|
*/

Route::get('/health', [HealthController::class, 'index']);

// ---- PHASE 2: Authentication ----
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// ---- PHASE 2: User profile (hanya untuk user yang login) ----
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [ProfileController::class, 'update']);

    // ---- PHASE 13: Notification (semua user yang login) ----
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
});

// ---- PHASE 4: School & Teacher (docs/ai/ROADMAP.json) ----
Route::middleware('auth:sanctum')->group(function () {
    // Sekolah: lihat untuk semua user login, kelola hanya admin.
    Route::get('/schools', [SchoolController::class, 'index']);
    Route::get('/schools/{school}', [SchoolController::class, 'show']);
    Route::post('/schools/resolve', [SchoolController::class, 'resolve']);

    // Skills: daftar semua skill (read-only, untuk form lowongan).
    Route::get('/skills', [SkillController::class, 'index']);

    // Jurusan: daftar per sekolah (kelola via MajorPolicy: guru sekolah/admin).
    Route::get('/schools/{school}/majors', [MajorController::class, 'index']);
    Route::post('/schools/{school}/majors', [MajorController::class, 'store']);
    Route::put('/majors/{major}', [MajorController::class, 'update']);
    Route::delete('/majors/{major}', [MajorController::class, 'destroy']);

    // Profil guru (profil sendiri) & dashboard guru: khusus role teacher.
    Route::middleware('role:teacher')->group(function () {
        Route::get('/me/teacher', [TeacherProfileController::class, 'show']);
        Route::post('/me/teacher', [TeacherProfileController::class, 'store']);
        Route::put('/me/teacher', [TeacherProfileController::class, 'update']);
        Route::get('/teacher/dashboard', [TeacherDashboardController::class, 'show']);

        // ---- PHASE 6: Partnership (sisi guru) ----
        Route::get('/teacher/partnerships/companies', [TeacherPartnershipController::class, 'searchCompanies']);
        Route::post('/teacher/partnerships', [TeacherPartnershipController::class, 'store']);
        Route::get('/teacher/partnerships', [TeacherPartnershipController::class, 'index']);
        Route::get('/teacher/partnerships/{partnership}', [TeacherPartnershipController::class, 'show']);
        Route::put('/teacher/partnerships/{partnership}', [TeacherPartnershipController::class, 'update']);
    });

    // Pengelolaan sekolah: khusus admin (ROLES.json: admin manage schools).
    Route::middleware('role:admin')->group(function () {
        Route::post('/schools', [SchoolController::class, 'store']);
        Route::put('/schools/{school}', [SchoolController::class, 'update']);
        Route::delete('/schools/{school}', [SchoolController::class, 'destroy']);

        // ---- PHASE 15: Reporting (admin) ----
        Route::get('/admin/reports/overview', [AdminReportController::class, 'overview']);
        Route::get('/admin/reports/placement', [AdminReportController::class, 'placement']);
        Route::get('/admin/reports/no-internship', [AdminReportController::class, 'noInternship']);
        Route::get('/admin/reports/partnerships', [AdminReportController::class, 'partnerships']);
        Route::get('/admin/reports/internships', [AdminReportController::class, 'internships']);
        Route::get('/admin/reports/applications', [AdminReportController::class, 'applications']);
    });

    // ---- PHASE 5: Company (docs/ai/ROADMAP.json) ----
    // Profil perusahaan (profil sendiri).
    Route::middleware('role:company')->group(function () {
        Route::get('/me/company', [CompanyProfileController::class, 'show']);
        Route::post('/me/company', [CompanyProfileController::class, 'store']);
        Route::put('/me/company', [CompanyProfileController::class, 'update']);

        // Dashboard perusahaan.
        Route::get('/company/dashboard', [CompanyDashboardController::class, 'show']);

        // Pengelolaan partnership dari sisi perusahaan.
        // Perusahaan mengajukan, guru yang menyetujui/menolak.
        Route::post('/company/partnerships', [CompanyPartnershipController::class, 'store']);
        Route::get('/company/partnerships', [CompanyPartnershipController::class, 'index']);
        Route::get('/company/partnerships/{partnership}', [CompanyPartnershipController::class, 'show']);
        Route::delete('/company/partnerships/{partnership}', [CompanyPartnershipController::class, 'cancel']);

        // ---- PHASE 7: Internship (company CRUD lowongan PKL) ----
        Route::get('/company/internships', [CompanyInternshipController::class, 'index']);
        Route::post('/company/internships', [CompanyInternshipController::class, 'store']);
        Route::get('/company/internships/{internship}', [CompanyInternshipController::class, 'show']);
        Route::put('/company/internships/{internship}', [CompanyInternshipController::class, 'update']);
        Route::delete('/company/internships/{internship}', [CompanyInternshipController::class, 'destroy']);
    });

    // ---- PHASE 8: Student Profile (profil siswa, skills, portfolio) ----
    Route::middleware('role:student')->group(function () {
        // Profil siswa.
        Route::get('/me/student', [StudentProfileController::class, 'show']);
        Route::post('/me/student', [StudentProfileController::class, 'store']);
        Route::put('/me/student', [StudentProfileController::class, 'update']);

        // Skill siswa.
        Route::get('/me/student/skills', [StudentSkillController::class, 'index']);
        Route::post('/me/student/skills', [StudentSkillController::class, 'store']);
        Route::put('/me/student/skills', [StudentSkillController::class, 'sync']);
        Route::delete('/me/student/skills/{skill}', [StudentSkillController::class, 'destroy']);

        // Portofolio siswa.
        Route::get('/me/student/portfolios', [StudentPortfolioController::class, 'index']);
        Route::post('/me/student/portfolios', [StudentPortfolioController::class, 'store']);
        Route::get('/me/student/portfolios/{portfolio}', [StudentPortfolioController::class, 'show']);
        Route::put('/me/student/portfolios/{portfolio}', [StudentPortfolioController::class, 'update']);
        Route::delete('/me/student/portfolios/{portfolio}', [StudentPortfolioController::class, 'destroy']);

        // Dokumen siswa (CV, portofolio, dll).
        Route::get('/me/student/documents', [StudentDocumentController::class, 'index']);
        Route::post('/me/student/documents', [StudentDocumentController::class, 'store']);
        Route::delete('/me/student/documents/{document}', [StudentDocumentController::class, 'destroy']);

        // Sertifikat siswa.
        Route::get('/me/student/certificates', [StudentCertificateController::class, 'index']);
        Route::post('/me/student/certificates', [StudentCertificateController::class, 'store']);
        Route::get('/me/student/certificates/{certificate}', [StudentCertificateController::class, 'show']);
        Route::put('/me/student/certificates/{certificate}', [StudentCertificateController::class, 'update']);
        Route::delete('/me/student/certificates/{certificate}', [StudentCertificateController::class, 'destroy']);

        // ---- PHASE 9: Application (siswa mendaftar PKL) ----
        // Jelajahi lowongan.
        Route::get('/student/internships/filters', [StudentInternshipController::class, 'filters']);
        Route::get('/student/internships', [StudentInternshipController::class, 'index']);
        Route::get('/student/internships/{internship}', [StudentInternshipController::class, 'show']);

        // Lamar ke lowongan.
        Route::post('/student/internships/{internship}/apply', [StudentInternshipController::class, 'apply']);

        // Lihat lamaran saya.
        Route::get('/student/applications', [StudentInternshipController::class, 'myApplications']);
        Route::get('/student/applications/{application}', [StudentInternshipController::class, 'showApplication']);
        Route::delete('/student/applications/{application}', [StudentInternshipController::class, 'cancel']);

        // Simpan/hapus lowongan.
        Route::post('/student/internships/{internship}/save', [StudentInternshipController::class, 'save']);
        Route::delete('/student/internships/{internship}/save', [StudentInternshipController::class, 'unsave']);
        Route::get('/student/saved-internships', [StudentInternshipController::class, 'savedInternships']);

        // ---- PHASE 11: Interview (sisi siswa) ----
        Route::get('/student/interviews', [StudentInterviewController::class, 'index']);
        Route::get('/student/interviews/{interview}', [StudentInterviewController::class, 'show']);

        // ---- PHASE 14: Smart Matching (rekomendasi lowongan) ----
        Route::get('/student/matchings', [SmartMatchingController::class, 'index']);
        Route::get('/student/matchings/{internship}/detail', [SmartMatchingController::class, 'detail']);
    });        // ---- PHASE 9: Application (sisi perusahaan) ----
        Route::middleware('role:company')->group(function () {
            // Pengelolaan pelamar.
            Route::get('/company/applications', [CompanyApplicationController::class, 'index']);
            Route::get('/company/applications/{application}', [CompanyApplicationController::class, 'show']);
            Route::put('/company/applications/{application}/status', [CompanyApplicationController::class, 'updateStatus']);
            Route::post('/company/applications/{application}/interview', [CompanyApplicationController::class, 'scheduleInterview']);

            // ---- PHASE 10: Selection (seleksi lanjutan) ----
            Route::get('/company/selection/stats', [CompanySelectionController::class, 'stats']);
            Route::get('/company/selection/top-applicants', [CompanySelectionController::class, 'topApplicants']);
            Route::put('/company/applications/{application}/rate', [CompanySelectionController::class, 'rate']);
            Route::put('/company/selection/batch', [CompanySelectionController::class, 'batchUpdate']);

            // ---- PHASE 11: Interview (sisi perusahaan) ----
            Route::get('/company/interviews', [CompanyInterviewController::class, 'index']);
            Route::get('/company/interviews/{interview}', [CompanyInterviewController::class, 'show']);
            Route::put('/company/interviews/{interview}/complete', [CompanyInterviewController::class, 'complete']);
            Route::put('/company/interviews/{interview}/cancel', [CompanyInterviewController::class, 'cancel']);

            // ---- PHASE 15: Reporting (company) ----
            Route::get('/company/reports/applications', [CompanyReportController::class, 'applications']);
            Route::get('/company/reports/internships', [CompanyReportController::class, 'internships']);
            Route::get('/company/reports/partnerships', [CompanyReportController::class, 'partnerships']);
        });        // ---- PHASE 9: Application (sisi guru) ----
        Route::middleware('role:teacher')->group(function () {
            Route::get('/teacher/applications/stats', [TeacherApplicationController::class, 'stats']);
            Route::get('/teacher/applications', [TeacherApplicationController::class, 'index']);
            Route::get('/teacher/applications/{application}', [TeacherApplicationController::class, 'show']);

            // ---- PHASE 12: Teacher Monitoring ----
            Route::get('/teacher/monitoring/overview', [TeacherMonitoringController::class, 'overview']);
            Route::get('/teacher/monitoring/students', [TeacherMonitoringController::class, 'students']);
            Route::get('/teacher/monitoring/students/{student}', [TeacherMonitoringController::class, 'showStudent']);


        });
});
