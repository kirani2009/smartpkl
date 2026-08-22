<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 8 — Student Profile.
 * Skill siswa (add, sync, remove).
 */
class StudentSkillTest extends TestCase
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

    public function test_student_can_list_own_skills(): void
    {
        $skill = Skill::create(['name' => 'PHP']);
        $this->studentUser->student->skills()->attach($skill->id, ['level' => 'advanced']);

        $token = $this->studentToken();

        $this->withToken($token)
            ->getJson('/api/me/student/skills')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.name', 'PHP');
    }

    public function test_student_can_add_skill(): void
    {
        $skill = Skill::create(['name' => 'Laravel']);
        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student/skills', [
                'skill_id' => $skill->id,
                'level' => 'intermediate',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Laravel')
            ->assertJsonPath('data.level', 'intermediate');

        $this->assertDatabaseHas('student_skills', [
            'student_id' => $this->studentUser->student->id,
            'skill_id' => $skill->id,
            'level' => 'intermediate',
        ]);
    }

    public function test_student_cannot_add_duplicate_skill(): void
    {
        $skill = Skill::create(['name' => 'PHP']);
        $this->studentUser->student->skills()->attach($skill->id, ['level' => 'beginner']);

        $token = $this->studentToken();

        $this->withToken($token)
            ->postJson('/api/me/student/skills', [
                'skill_id' => $skill->id,
                'level' => 'advanced',
            ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);
    }

    public function test_student_can_sync_skills(): void
    {
        $skill1 = Skill::create(['name' => 'PHP']);
        $skill2 = Skill::create(['name' => 'JavaScript']);
        $skill3 = Skill::create(['name' => 'Python']);

        $this->studentUser->student->skills()->attach($skill1->id, ['level' => 'beginner']);

        $token = $this->studentToken();

        // Sync: remove PHP, add JavaScript and Python
        $this->withToken($token)
            ->putJson('/api/me/student/skills', [
                'skills' => [
                    ['skill_id' => $skill2->id, 'level' => 'advanced'],
                    ['skill_id' => $skill3->id, 'level' => 'intermediate'],
                ],
            ])
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');

        $this->assertDatabaseMissing('student_skills', [
            'student_id' => $this->studentUser->student->id,
            'skill_id' => $skill1->id,
        ]);
        $this->assertDatabaseHas('student_skills', [
            'student_id' => $this->studentUser->student->id,
            'skill_id' => $skill2->id,
            'level' => 'advanced',
        ]);
    }

    public function test_student_can_remove_skill(): void
    {
        $skill = Skill::create(['name' => 'PHP']);
        $this->studentUser->student->skills()->attach($skill->id);

        $token = $this->studentToken();

        $this->withToken($token)
            ->deleteJson("/api/me/student/skills/{$skill->id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('student_skills', [
            'student_id' => $this->studentUser->student->id,
            'skill_id' => $skill->id,
        ]);
    }

    public function test_remove_nonexistent_skill_returns_404(): void
    {
        $skill = Skill::create(['name' => 'PHP']);
        $token = $this->studentToken();

        $this->withToken($token)
            ->deleteJson("/api/me/student/skills/{$skill->id}")
            ->assertStatus(404);
    }

    public function test_student_without_profile_gets_404(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me/student/skills')
            ->assertStatus(404);

        $this->withToken($token)
            ->postJson('/api/me/student/skills', ['skill_id' => 1])
            ->assertStatus(404);
    }

    public function test_non_student_cannot_access_skills(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $token = $teacher->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me/student/skills')
            ->assertStatus(403);
    }
}
