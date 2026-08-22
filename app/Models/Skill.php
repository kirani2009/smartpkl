<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * PHASE 3 — Database Core.
 * Daftar skill, dipakai di profil siswa dan kebutuhan lowongan (Smart Matching).
 */
class Skill extends Model
{
    protected $fillable = [
        'name',
    ];

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_skills')
            ->withPivot('level')
            ->withTimestamps();
    }

    public function internships(): BelongsToMany
    {
        return $this->belongsToMany(InternshipListing::class, 'internship_skills', 'skill_id', 'internship_id')
            ->withTimestamps();
    }
}
