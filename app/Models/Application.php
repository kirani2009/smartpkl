<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * PHASE 3 — Database Core.
 * Lamaran siswa ke lowongan PKL (docs/ai/APPLICATION.json).
 */
class Application extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_REVIEWED = 'REVIEWED';
    public const STATUS_INTERVIEW = 'INTERVIEW';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'internship_id',
        'student_id',
        'status',
        'message',
        'rating',
        'selection_notes',
        'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(InternshipListing::class, 'internship_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    /**
     * Interview aktif (satu lamaran maksimal satu interview aktif).
     */
    public function activeInterview(): HasOne
    {
        return $this->hasOne(Interview::class)
            ->where('status', Interview::STATUS_SCHEDULED);
    }
}
