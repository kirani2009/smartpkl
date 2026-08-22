<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PHASE 3 — Database Core.
 * Sertifikat siswa.
 */
class Certificate extends Model
{
    protected $fillable = [
        'student_id',
        'title',
        'issuer',
        'issued_at',
        'file_path',
    ];

    protected $casts = [
        'issued_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
