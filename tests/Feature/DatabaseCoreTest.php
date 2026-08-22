<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationStatusHistory;
use App\Models\Certificate;
use App\Models\Company;
use App\Models\CompanyProfile;
use App\Models\Document;
use App\Models\Interview;
use App\Models\InternshipListing;
use App\Models\InternshipRequirement;
use App\Models\Major;
use App\Models\Notification;
use App\Models\Portfolio;
use App\Models\SavedInternship;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\Skill;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 3 — Database Core.
 * Memvalidasi relasi Eloquent dan constraint database sesuai docs/ai/DATABASE.json.
 */
class DatabaseCoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Membuat data dasar: sekolah, guru, siswa, perusahaan, partnership, lowongan, lamaran.
     */
    private function seedBase(): array
    {
        $school = School::create(['name' => 'SMK N 1 Bandung', 'city' => 'Bandung']);
        $major = Major::create(['school_id' => $school->id, 'name' => 'Rekayasa Perangkat Lunak', 'code' => 'RPL']);

        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'school_id' => $school->id,
            'position' => 'Guru Pembimbing PKL',
        ]);

        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'school_id' => $school->id,
            'major_id' => $major->id,
            'class' => 'XI RPL 1',
            'entry_year' => 2025,
        ]);

        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create([
            'user_id' => $companyUser->id,
            'status' => Company::STATUS_ACTIVE,
        ]);
        $companyProfile = CompanyProfile::create([
            'company_id' => $company->id,
            'name' => 'PT Maju Jaya',
            'industry' => 'Teknologi',
            'city' => 'Bandung',
        ]);

        $partnership = SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'requested_by' => $teacherUser->id,
            'status' => SchoolCompanyPartnership::STATUS_ACCEPTED,
            'responded_at' => now(),
        ]);

        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'school_id' => $school->id,
            'major_id' => $major->id,
            'title' => 'Backend Developer Intern',
            'position' => 'Backend Developer',
            'description' => 'Membantu pengembangan API perusahaan.',
            'quota' => 3,
            'period_start' => '2026-09-01',
            'period_end' => '2026-12-31',
            'location' => 'Bandung',
            'allowance' => 1000000.00,
            'status' => InternshipListing::STATUS_PUBLISHED,
        ]);

        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => Application::STATUS_PENDING,
            'message' => 'Saya tertarik dengan lowongan ini.',
        ]);

        return compact(
            'school', 'major', 'teacher', 'student', 'company', 'companyProfile',
            'partnership', 'internship', 'application',
            'teacherUser', 'studentUser', 'companyUser'
        );
    }

    public function test_full_relationship_chain_works(): void
    {
        $base = $this->seedBase();
        extract($base);

        // Skill & dokumen siswa
        $skillLaravel = Skill::create(['name' => 'Laravel']);
        $skillMysql = Skill::create(['name' => 'MySQL']);
        $student->skills()->attach($skillLaravel->id, ['level' => 'intermediate']);
        $student->skills()->attach($skillMysql->id, ['level' => 'beginner']);

        $student->savedInternships()->create(['internship_id' => $internship->id]);
        $student->documents()->create([
            'type' => Document::TYPE_CV,
            'title' => 'CV Siswa',
            'file_path' => 'uploads/cv.pdf',
        ]);
        $student->certificates()->create([
            'title' => 'Juara LKS Web',
            'issuer' => 'Disdik Jabar',
            'issued_at' => '2025-05-10',
        ]);
        $student->portfolios()->create([
            'title' => 'Aplikasi Kasir',
            'url' => 'https://github.com/student/kasir',
        ]);

        // Riwayat status + interview
        $application->statusHistories()->create([
            'status' => Application::STATUS_PENDING,
            'changed_by' => $studentUser->id,
        ]);
        $application->statusHistories()->create([
            'status' => Application::STATUS_INTERVIEW,
            'changed_by' => $companyUser->id,
            'note' => 'Diundang interview online.',
        ]);
        $interview = $application->interviews()->create([
            'scheduled_at' => now()->addDays(2),
            'mode' => 'online',
            'status' => Interview::STATUS_SCHEDULED,
        ]);

        // Persyaratan & skill lowongan
        $internship->requirements()->create(['description' => 'Minimal kelas XI']);
        $internship->requirements()->create(['description' => 'Bisa Laravel dasar']);
        $internship->skills()->attach([$skillLaravel->id, $skillMysql->id]);

        // Notifikasi
        $notification = Notification::create([
            'user_id' => $studentUser->id,
            'type' => 'application_reviewed',
            'title' => 'Lamaran direview',
            'message' => 'Perusahaan sedang meninjau lamaran Anda.',
            'data' => ['application_id' => $application->id],
        ]);

        // ---- Asersi relasi (docs/ai/DATABASE.json relationship_rules) ----
        $this->assertTrue($school->teachers->contains('id', $teacher->id));
        $this->assertTrue($school->students->contains('id', $student->id));
        $this->assertTrue($school->majors->contains('id', $major->id));
        $this->assertTrue($school->partnerships->contains('id', $partnership->id));

        $this->assertTrue($company->profile->is($companyProfile));
        $this->assertTrue($company->partnerships->contains('id', $partnership->id));
        $this->assertTrue($company->internshipListings->contains('id', $internship->id));

        $this->assertTrue($student->user->is($studentUser));
        $this->assertTrue($student->school->is($school));
        $this->assertTrue($student->major->is($major));
        $this->assertCount(2, $student->skills);
        $this->assertTrue($student->applications->contains('id', $application->id));
        $this->assertTrue($student->savedInternships->contains('internship_id', $internship->id));
        $this->assertCount(1, $student->documents);
        $this->assertCount(1, $student->certificates);
        $this->assertCount(1, $student->portfolios);

        $this->assertTrue($internship->company->is($company));
        $this->assertTrue($internship->school->is($school));
        $this->assertCount(2, $internship->requirements);
        $this->assertCount(2, $internship->skills);
        $this->assertTrue($internship->skills->contains('name', 'Laravel'));
        $this->assertTrue($internship->applications->contains('id', $application->id));
        $this->assertInstanceOf(InternshipRequirement::class, $internship->requirements->first());

        $this->assertTrue($application->internship->is($internship));
        $this->assertTrue($application->student->is($student));
        $this->assertCount(2, $application->statusHistories);
        $this->assertInstanceOf(ApplicationStatusHistory::class, $application->statusHistories->first());
        $this->assertTrue($application->interviews->contains('id', $interview->id));
        $this->assertTrue($application->activeInterview->is($interview));

        $this->assertTrue($notification->user->is($studentUser));
        $this->assertEquals('application_reviewed', $notification->type);
        $this->assertSame($application->id, $notification->data['application_id']);

        // Soft-ish checks: dokumen/sertifikat/portofolio milik siswa
        $this->assertTrue($student->documents->first()->student->is($student));
        $this->assertInstanceOf(Certificate::class, $student->certificates->first());
        $this->assertInstanceOf(Portfolio::class, $student->portfolios->first());
    }

    public function test_student_cannot_apply_twice_to_same_internship(): void
    {
        $base = $this->seedBase();
        extract($base);

        $this->expectException(QueryException::class);

        Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => Application::STATUS_PENDING,
        ]);
    }

    public function test_student_cannot_save_same_internship_twice(): void
    {
        $base = $this->seedBase();
        extract($base);

        $student->savedInternships()->create(['internship_id' => $internship->id]);

        $this->expectException(QueryException::class);

        SavedInternship::create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
        ]);
    }

    public function test_student_skill_pair_is_unique(): void
    {
        $base = $this->seedBase();
        extract($base);

        $skill = Skill::create(['name' => 'Laravel']);
        $student->skills()->attach($skill->id);

        $this->expectException(QueryException::class);

        $student->skills()->attach($skill->id);
    }

    public function test_duplicate_partnership_pair_is_rejected(): void
    {
        $base = $this->seedBase();
        extract($base);

        $this->expectException(QueryException::class);

        SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'status' => SchoolCompanyPartnership::STATUS_PENDING,
        ]);
    }

    public function test_deleting_user_cascades_to_student_profile(): void
    {
        $base = $this->seedBase();
        extract($base);

        $student->user->delete();

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $studentUser->id]);
    }

    public function test_deleting_internship_cascades_to_applications(): void
    {
        $base = $this->seedBase();
        extract($base);

        $applicationId = $application->id;
        $internship->delete();

        $this->assertDatabaseMissing('internship_listings', ['id' => $internship->id]);
        $this->assertDatabaseMissing('applications', ['id' => $applicationId]);
    }
}
