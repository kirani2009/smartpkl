<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PHASE 3 — Database Core.
 * Profil siswa (user role student).
 * One student has many applications, skills, documents, certificates, portfolios.
 */
class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'school_id',
        'school_name',
        'major_id',
        'major_name',
        'nis',
        'class',
        'entry_year',
        'gender',
        'birth_date',
        'phone',
        'address',
        'interests',
    ];

    protected $casts = [
        'entry_year' => 'integer',
        'birth_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'student_skills')
            ->withPivot('level')
            ->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function savedInternships(): HasMany
    {
        return $this->hasMany(SavedInternship::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }
}
