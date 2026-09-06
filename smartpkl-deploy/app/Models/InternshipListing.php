<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PHASE 3 — Database Core.
 * Lowongan PKL (docs/ai/INTERNSHIP.json).
 */
class InternshipListing extends Model
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_CLOSED = 'CLOSED';
    public const STATUS_EXPIRED = 'EXPIRED';

    protected $fillable = [
        'company_id',
        'school_id',
        'major_id',
        'title',
        'position',
        'description',
        'quota',
        'period_start',
        'period_end',
        'location',
        'required_skills',
        'required_major',
        'status',
    ];

    protected $casts = [
        'quota' => 'integer',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Sekolah yang menjadi target lowongan (null = terbuka untuk semua sekolah partner).
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function major(): BelongsTo
    {
        return $this->belongsTo(Major::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(InternshipRequirement::class, 'internship_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'internship_id');
    }

    public function savedByStudents(): HasMany
    {
        return $this->hasMany(SavedInternship::class, 'internship_id');
    }

    /**
     * Skill yang dibutuhkan lowongan (input Smart Matching).
     */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'internship_skills', 'internship_id', 'skill_id')
            ->withTimestamps();
    }
}
