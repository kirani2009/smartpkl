<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PHASE 3 — Database Core.
 * Jurusan/kompetensi keahlian. school_id null = jurusan umum (lintas sekolah).
 */
class Major extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
