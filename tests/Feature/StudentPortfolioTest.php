<?php

namespace Tests\Feature;

use App\Models\Portfolio;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — Student Profile.
 * Portofolio siswa (CRUD).
 */
class StudentPortfolioTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();

        $school = School::create(['name' => 'SMK N 1']);
        $this->studentUser = User::factory()->create(['role' => 'student']);
        $this->studentUser->student()->create(['school_id' => $school->id]);
    }

    private function studentToken(): string
    {
        return $this->studentUser->createToken('auth-token')->plainTextToken;
    }

    public function test_student_can_create_portfolio(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student/portfolios', [
                'title' => 'Website E-Commerce',
                'description' => 'Membangun website e-commerce menggunakan Laravel',
                'url' => 'https://github.com/user/ecommerce',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'Website E-Commerce')
            ->assertJsonPath('data.url', 'https://github.com/user/ecommerce');

        $this->assertDatabaseHas('portfolios', [
            'student_id' => $this->studentUser->student->id,
            'title' => 'Website E-Commerce',
        ]);
    }

    public function test_student_can_list_portfolios(): void
    {
        $this->studentUser->student->portfolios()->create([
            'title' => 'Portfolio 1',
        ]);
        $this->studentUser->student->portfolios()->create([
            'title' => 'Portfolio 2',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/me/student/portfolios')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_student_can_view_own_portfolio_detail(): void
    {
        $portfolio = $this->studentUser->student->portfolios()->create([
            'title' => 'My Project',
            'description' => 'A cool project',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/me/student/portfolios/{$portfolio->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'My Project');
    }

    public function test_student_can_update_portfolio(): void
    {
        $portfolio = $this->studentUser->student->portfolios()->create([
            'title' => 'Old Title',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->putJson("/api/me/student/portfolios/{$portfolio->id}", [
                'title' => 'New Title',
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.title', 'New Title');
    }

    public function test_student_can_delete_portfolio(): void
    {
        $portfolio = $this->studentUser->student->portfolios()->create([
            'title' => 'To Delete',
        ]);

        $token = $this->studentToken();

        $this->withToken($token)
            ->deleteJson("/api/me/student/portfolios/{$portfolio->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('portfolios', ['id' => $portfolio->id]);
    }

    public function test_student_cannot_access_other_students_portfolio(): void
    {
        $otherUser = User::factory()->create(['role' => 'student']);
        $otherSchool = School::create(['name' => 'SMK N 2']);
        $otherStudent = $otherUser->student()->create(['school_id' => $otherSchool->id]);
        $portfolio = $otherStudent->portfolios()->create(['title' => 'Other']);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson("/api/me/student/portfolios/{$portfolio->id}")
            ->assertStatus(403);
    }

    public function test_create_requires_title(): void
    {
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student/portfolios', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_student_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me/student/portfolios')
            ->assertStatus(404);

        $this->withToken($token)
            ->postJson('/api/me/student/portfolios', ['title' => 'Test'])
            ->assertStatus(404);
    }
}
