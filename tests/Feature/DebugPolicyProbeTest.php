<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies cross-school authorization: a teacher from School A
 * must NOT be able to manage majors belonging to School B.
 */
class DebugPolicyProbeTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_create_major_for_another_school(): void
    {
        $schoolA = School::create(['name' => 'SMK A']);
        $schoolB = School::create(['name' => 'SMK B']);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->teacher()->create(['school_id' => $schoolA->id]);

        $token = $teacher->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/schools/{$schoolB->id}/majors", ['name' => 'Illegal Major']);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonFragment([
                'message' => 'Forbidden: Anda tidak memiliki akses ke resource ini.',
            ]);

        $this->assertDatabaseMissing('majors', [
            'name' => 'Illegal Major',
            'school_id' => $schoolB->id,
        ]);
    }

    public function test_teacher_cannot_update_major_for_another_school(): void
    {
        $schoolA = School::create(['name' => 'SMK A']);
        $schoolB = School::create(['name' => 'SMK B']);

        $major = $schoolB->majors()->create(['name' => 'RPL']);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->teacher()->create(['school_id' => $schoolA->id]);

        $token = $teacher->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)
            ->putJson("/api/majors/{$major->id}", ['name' => 'Hacked']);

        $response->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('majors', [
            'id' => $major->id,
            'name' => 'RPL',
        ]);
    }

    public function test_teacher_can_create_major_for_own_school(): void
    {
        $school = School::create(['name' => 'SMK A']);

        $teacher = User::factory()->create(['role' => 'teacher']);
        $teacher->teacher()->create(['school_id' => $school->id]);

        $token = $teacher->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson("/api/schools/{$school->id}/majors", ['name' => 'TKJ']);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'TKJ');

        $this->assertDatabaseHas('majors', [
            'name' => 'TKJ',
            'school_id' => $school->id,
        ]);
    }
}
