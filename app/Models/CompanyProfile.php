<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PHASE 3 — Database Core.
 * Detail profil perusahaan (satu perusahaan satu profil).
 */
class CompanyProfile extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'industry',
        'address',
        'city',
        'phone',
        'email',
        'website',
        'description',
        'logo',
        'established_year',
        'employee_count',
    ];

    protected $casts = [
        'established_year' => 'integer',
        'employee_count' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
