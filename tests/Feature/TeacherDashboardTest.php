<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Company;
use App\Models\InternshipListing;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 4 — School & Teacher.
 * Dashboard guru: statistik sekolah tempat guru mengajar.
 */
class TeacherDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function seedSchoolWithTeacher(): array
    {
        $school = School::create(['name' => 'SMK N 1 Bandung']);
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = $teacherUser->teacher()->create([
            'school_id' => $school->id,
            'position' => 'Guru Pembimbing',
        ]);
        $token = $teacherUser->createToken('auth-token')->plainTextToken;

        return compact('school', 'teacher', 'token');
    }

    public function test_teacher_dashboard_returns_stats(): void
    {
        ['school' => $school, 'token' => $token] = $this->seedSchoolWithTeacher();

        // 2 siswa, 1 sudah ditempatkan.
        $student1 = Student::create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'school_id' => $school->id,
        ]);
        $student2 = Student::create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'school_id' => $school->id,
        ]);

        // Partnership aktif + pending.
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = Company::create(['user_id' => $companyUser->id, 'status' => Company::STATUS_ACTIVE]);
        SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company->id,
            'status' => SchoolCompanyPartnership::STATUS_ACCEPTED,
        ]);
        $company2 = Company::create([
            'user_id' => User::factory()->create(['role' => 'company'])->id,
            'status' => Company::STATUS_ACTIVE,
        ]);
        SchoolCompanyPartnership::create([
            'school_id' => $school->id,
            'company_id' => $company2->id,
            'status' => SchoolCompanyPartnership::STATUS_PENDING,
        ]);

        // 1 lamaran diterima.
        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Intern Web Developer',
            'description' => 'Deskripsi',
            'quota' => 2,
            'period_start' => '2026-09-01',
            'period_end' => '2026-12-31',
            'status' => InternshipListing::STATUS_PUBLISHED,
        ]);
        Application::create([
            'internship_id' => $internship->id,
            'student_id' => $student1->id,
            'status' => Application::STATUS_ACCEPTED,
        ]);

        $this->withToken($token)
            ->getJson('/api/teacher/dashboard')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.school.name', 'SMK N 1 Bandung')
            ->assertJsonPath('data.students.total', 2)
            ->assertJsonPath('data.students.placed', 1)
            ->assertJsonPath('data.students.without_internship', 1)
            ->assertJsonPath('data.partnerships.total', 2)
            ->assertJsonPath('data.partnerships.active', 1)
            ->assertJsonPath('data.partnerships.pending', 1)
            ->assertJsonCount(1, 'data.recent_applications')
            ->assertJsonPath('data.recent_applications.0.student_name', $student1->user->name)
            ->assertJsonPath('data.recent_applications.0.internship_title', 'Intern Web Developer');
    }

    public function test_teacher_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/dashboard')
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    public function test_non_teacher_cannot_access_dashboard(): void
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $token = $companyUser->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/teacher/dashboard')
            ->assertStatus(403);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/teacher/dashboard')->assertStatus(401);
    }
}
