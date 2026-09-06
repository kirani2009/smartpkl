<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PHASE 3 — Database Core.
 * Dokumen siswa (CV, portofolio, dll).
 */
class Document extends Model
{
    public const TYPE_CV = 'CV';
    public const TYPE_PORTFOLIO = 'PORTFOLIO';
    public const TYPE_OTHER = 'OTHER';

    protected $fillable = [
        'student_id',
        'type',
        'title',
        'file_path',
        'file_size',
        'mime_type',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
