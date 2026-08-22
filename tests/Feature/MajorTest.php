<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHASE 4 — School & Teacher.
 * CRUD jurusan: guru dari sekolah terkait atau admin.
 */
class MajorTest extends TestCase
{
    use RefreshDatabase;

    private function teacherAt(School $school): string
    {
        $user = User::factory()->create(['role' => 'teacher']);
        $user->teacher()->create(['school_id' => $school->id]);

        return $user->createToken('auth-token')->plainTextToken;
    }

    public function test_teacher_of_school_can_create_major(): void
    {
        $school = School::create(['name' => 'SMK N 1 Bandung']);
        $token = $this->teacherAt($school);

        $this->withToken($token)
            ->postJson("/api/schools/{$school->id}/majors", [
                'name' => 'Rekayasa Perangkat Lunak',
                'code' => 'RPL',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Rekayasa Perangkat Lunak')
            ->assertJsonPath('data.school_id', $school->id);

        $this->assertDatabaseHas('majors', ['name' => 'Rekayasa Perangkat Lunak', 'school_id' => $school->id]);
    }

    public function test_admin_can_create_major_for_any_school(): void
    {
        $school = School::create(['name' => 'SMK N 2 Bogor']);
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/schools/{$school->id}/majors", ['name' => 'Akuntansi'])
            ->assertStatus(201);
    }

    public function test_teacher_of_other_school_cannot_create_major(): void
    {
        $schoolA = School::create(['name' => 'SMK A']);
        $schoolB = School::create(['name' => 'SMK B']);
        $token = $this->teacherAt($schoolA);

        $this->withToken($token)
            ->postJson("/api/schools/{$schoolB->id}/majors", ['name' => 'Illegal'])
            ->assertStatus(403)
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('majors', ['name' => 'Illegal']);
    }

    public function test_any_authenticated_user_can_list_majors(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $school->majors()->createMany([
            ['name' => 'RPL'],
            ['name' => 'TKJ'],
        ]);

        $student = User::factory()->create(['role' => 'student']);
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/schools/{$school->id}/majors")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.items');
    }

    public function test_teacher_can_update_and_delete_own_school_major(): void
    {
        $school = School::create(['name' => 'SMK N 1']);
        $major = $school->majors()->create(['name' => 'RPL']);
        $token = $this->teacherAt($school);

        $this->withToken($token)
            ->putJson("/api/majors/{$major->id}", ['name' => 'RPL (Baru)'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'RPL (Baru)');

        $this->withToken($token)
            ->deleteJson("/api/majors/{$major->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('majors', ['id' => $major->id]);
    }

    public function test_teacher_cannot_update_major_of_other_school(): void
    {
        $schoolA = School::create(['name' => 'SMK A']);
        $schoolB = School::create(['name' => 'SMK B']);
        $majorB = $schoolB->majors()->create(['name' => 'TKJ']);
        $token = $this->teacherAt($schoolA);

        $this->withToken($token)
            ->putJson("/api/majors/{$majorB->id}", ['name' => 'Diubah'])
            ->assertStatus(403);
    }

    public function test_major_endpoints_require_authentication(): void
    {
        $this->getJson('/api/schools/1/majors')->assertStatus(401);
        $this->postJson('/api/schools/1/majors', ['name' => 'X'])->assertStatus(401);
    }
}
