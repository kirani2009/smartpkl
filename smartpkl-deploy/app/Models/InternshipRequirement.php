<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PHASE 3 — Database Core.
 * Persyaratan lowongan PKL.
 */
class InternshipRequirement extends Model
{
    protected $fillable = [
        'internship_id',
        'description',
    ];

    public function internship(): BelongsTo
    {
        return $this->belongsTo(InternshipListing::class, 'internship_id');
    }
}
