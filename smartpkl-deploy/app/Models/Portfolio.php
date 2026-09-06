<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PHASE 3 — Database Core.
 * Portofolio siswa.
 */
class Portfolio extends Model
{
    protected $fillable = [
        'student_id',
        'title',
        'description',
        'url',
        'file_path',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
