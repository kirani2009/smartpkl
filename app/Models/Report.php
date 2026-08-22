<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PHASE 3 — Database Core.
 * Laporan yang dihasilkan sistem (docs/ai/REPORTING.json).
 */
class Report extends Model
{
    protected $fillable = [
        'type',
        'title',
        'school_id',
        'company_id',
        'period_start',
        'period_end',
        'data',
        'generated_by',
        'file_path',
    ];

    protected $casts = [
        'data' => 'array',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
