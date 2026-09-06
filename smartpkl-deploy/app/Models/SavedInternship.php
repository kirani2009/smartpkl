<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PHASE 3 — Database Core.
 * Lowongan yang disimpan siswa (bookmark).
 */
class SavedInternship extends Model
{
    protected $fillable = [
        'student_id',
        'internship_id',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function internship(): BelongsTo
    {
        return $this->belongsTo(InternshipListing::class, 'internship_id');
    }
}
