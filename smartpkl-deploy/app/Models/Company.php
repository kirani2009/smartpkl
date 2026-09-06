<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * PHASE 3 — Database Core.
 * Akun perusahaan (user role company).
 * One company can have many school partnerships and internship listings.
 */
class Company extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'school_id',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(SchoolCompanyPartnership::class);
    }

    public function internshipListings(): HasMany
    {
        return $this->hasMany(InternshipListing::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
