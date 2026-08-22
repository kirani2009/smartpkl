<?php

namespace Tests\Feature;

use App\Models\InternshipListing;
use App\Models\Major;
use App\Models\School;
use App\Models\SchoolCompanyPartnership;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 14 — Smart Matching.
 * Rekomendasi lowongan PKL berdasarkan profil siswa (rule-based scoring).
 */
class SmartMatchingTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private \App\Models\Student $student;
    private School $school;
    private Major $major;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create(['name' => 'SMK N 1', 'city' => 'Bandung']);
        $this->major = Major::create(['name' => 'RPL', 'school_id' => $this->school->id]);

        $this->studentUser = User::factory()->create(['role' => 'student']);
        $this->student = $this->studentUser->student()->create([
            'school_id' => $this->school->id,
            'major_id' => $this->major->id,
            'interests' => 'Web Development, JavaScript',
        ]);

        // Setup partnership for school
        $companyUser = User::factory()->create(['role' => 'company']);
        $company = $companyUser->company()->create(['status' => 'active']);
        $company->profile()->create(['name' => 'PT Tech']);
        SchoolCompanyPartnership::create([
            'school_id' => $this->school->id,
            'company_id' => $company->id,
            'status' => 'ACCEPTED',
        ]);
    }

    private function studentToken(): string
    {
        return $this->studentUser->createToken('auth-token')->plainTextToken;
    }

    public function test_student_can_get_recommendations(): void
    {
        // Create skills
        $phpSkill = Skill::create(['name' => 'PHP']);
        $jsSkill = Skill::create(['name' => 'JavaScript']);
        $pythonSkill = Skill::create(['name' => 'Python']);

        $this->student->skills()->attach($phpSkill->id, ['level' => 'advanced']);
        $this->student->skills()->attach($jsSkill->id, ['level' => 'intermediate']);

        // Create internships
        $company = $this->school->partnerships->first()->company;

        $internship1 = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer',
            'description' => 'Web Development dengan JavaScript',
            'quota' => 2,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(60),
            'location' => 'Bandung',
            'status' => 'PUBLISHED',
        ]);
        $internship1->skills()->attach([$phpSkill->id, $jsSkill->id]);

        $internship2 = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Data Analyst',
            'description' => 'Analisis data dengan Python',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(60),
            'location' => 'Jakarta',
            'status' => 'PUBLISHED',
        ]);
        $internship2->skills()->attach([$pythonSkill->id]);

        $token = $this->studentToken();

        $response = $this->withToken($token)
            ->getJson('/api/student/matchings')
            ->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertCount(2, $items);

        // Web Developer should score higher (matches major, skills, interest, location)
        $this->assertGreaterThan($items[1]['match_score'], $items[0]['match_score']);
        $this->assertEquals('Web Developer', $items[0]['internship']['title']);
    }

    public function test_recommendations_sorted_by_score(): void
    {
        $company = $this->school->partnerships->first()->company;

        InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Low Match',
            'description' => 'Something unrelated',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(60),
            'location' => 'Jakarta',
            'status' => 'PUBLISHED',
        ]);

        InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'High Match',
            'description' => 'Web Development dengan JavaScript',
            'major_id' => $this->major->id,
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(60),
            'location' => 'Bandung',
            'status' => 'PUBLISHED',
        ]);

        $token = $this->studentToken();

        $response = $this->withToken($token)
            ->getJson('/api/student/matchings')
            ->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertEquals('High Match', $items[0]['internship']['title']);
        $this->assertEquals('Low Match', $items[1]['internship']['title']);
    }

    public function test_can_filter_by_min_score(): void
    {
        $company = $this->school->partnerships->first()->company;

        InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Unrelated',
            'description' => 'Completely unrelated job',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(60),
            'location' => 'Surabaya',
            'status' => 'PUBLISHED',
        ]);

        InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Perfect Match',
            'description' => 'Web Development dengan JavaScript',
            'major_id' => $this->major->id,
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(60),
            'location' => 'Bandung',
            'status' => 'PUBLISHED',
        ]);

        $token = $this->studentToken();

        // With high min_score, only the good match should show
        $response = $this->withToken($token)
            ->getJson('/api/student/matchings?min_score=50')
            ->assertStatus(200);

        $items = $response->json('data.items');
        $this->assertGreaterThanOrEqual(1, count($items));

        foreach ($items as $item) {
            $this->assertGreaterThanOrEqual(50, $item['match_score']);
        }
    }

    public function test_detail_scoring_for_one_internship(): void
    {
        $company = $this->school->partnerships->first()->company;
        $skill = Skill::create(['name' => 'PHP']);
        $this->student->skills()->attach($skill->id, ['level' => 'advanced']);

        $internship = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Web Developer',
            'description' => 'Web Development',
            'major_id' => $this->major->id,
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(60),
            'location' => 'Bandung',
            'status' => 'PUBLISHED',
        ]);
        $internship->skills()->attach([$skill->id]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/student/matchings/{$internship->id}/detail")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'internship',
                    'match_score',
                    'match_breakdown' => ['major', 'skills', 'interest', 'location', 'period'],
                    'match_weights',
                ],
            ]);
    }

    public function test_only_shows_published_internships(): void
    {
        $company = $this->school->partnerships->first()->company;

        InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Draft',
            'description' => 'Draft',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(60),
            'status' => 'DRAFT',
        ]);

        $published = InternshipListing::create([
            'company_id' => $company->id,
            'title' => 'Published',
            'description' => 'Published',
            'quota' => 1,
            'period_start' => now()->addDays(10),
            'period_end' => now()->addDays(60),
            'status' => 'PUBLISHED',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/student/matchings')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_student_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/student/matchings')
            ->assertStatus(404);
    }

    public function test_non_student_cannot_access(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $token = $teacher->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/student/matchings')
            ->assertStatus(403);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson('/api/student/matchings')->assertStatus(401);
    }
}
