<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Interview;
use App\Models\InternshipListing;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 11 — Interview.
 * Siswa melihat jadwal interview milik sendiri.
 */
class StudentInterviewTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private \App\Models\Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::create(['name' => 'SMK N 1']);
        $this->studentUser = User::factory()->create(['role' => 'student']);
        $this->student = $this->studentUser->student()->create(['school_id' => $school->id]);
    }

    private function studentToken(): string
    {
        return $this->studentUser->createToken('auth-token')->plainTextToken;
    }

    private function createMyInterview(string $status = 'SCHEDULED'): Interview
    {
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer',
            'description' => 'Desc',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);

        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $this->student->id,
            'status' => 'INTERVIEW',
            'applied_at' => now(),
        ]);

        return Interview::create([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDays(3),
            'mode' => 'onsite',
            'location' => 'Kantor PT',
            'status' => $status,
        ]);
    }

    public function test_student_can_list_own_interviews(): void
    {
        $this->createMyInterview();

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/student/interviews')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_student_cannot_see_other_students_interviews(): void
    {
        $this->createMyInterview();

        // Other student's interview
        $otherUser = User::factory()->create(['role' => 'student']);
        $otherStudent = $otherUser->student()->create(['school_id' => $this->student->school_id]);
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Other',
            'description' => 'Other',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);
        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $otherStudent->id,
            'status' => 'INTERVIEW',
            'applied_at' => now(),
        ]);
        Interview::create([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDays(3),
            'mode' => 'online',
            'status' => 'SCHEDULED',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/student/interviews')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items'); // only own
    }

    public function test_student_can_view_own_interview_detail(): void
    {
        $interview = $this->createMyInterview();
        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/student/interviews/{$interview->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.location', 'Kantor PT');
    }

    public function test_student_cannot_view_other_students_interview(): void
    {
        $otherUser = User::factory()->create(['role' => 'student']);
        $otherStudent = $otherUser->student()->create(['school_id' => $this->student->school_id]);
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Other',
            'description' => 'Other',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(40),
            'status' => 'PUBLISHED',
        ]);
        $application = Application::create([
            'internship_id' => $internship->id,
            'student_id' => $otherStudent->id,
            'status' => 'INTERVIEW',
            'applied_at' => now(),
        ]);
        $interview = Interview::create([
            'application_id' => $application->id,
            'scheduled_at' => now()->addDays(3),
            'mode' => 'online',
            'status' => 'SCHEDULED',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/student/interviews/{$interview->id}")
            ->assertStatus(403);
    }

    public function test_student_can_filter_by_status(): void
    {
        $this->createMyInterview('SCHEDULED');
        $this->createMyInterview('COMPLETED');

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/student/interviews?status=COMPLETED')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.status', 'COMPLETED');
    }

    public function test_student_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/student/interviews')
            ->assertStatus(404);
    }

    public function test_non_student_cannot_access(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $token = $teacher->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/student/interviews')
            ->assertStatus(403);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/student/interviews')->assertStatus(401);
    }
}
