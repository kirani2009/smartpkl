<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PHASE 3 — Database Core.
 * One school has many students, teachers, and company partnerships.
 */
class School extends Model
{
    protected $fillable = [
        'name',
        'npsn',
        'address',
        'city',
        'phone',
        'email',
        'logo',
        'description',
    ];

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    public function partnerships(): HasMany
    {
        return $this->hasMany(SchoolCompanyPartnership::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
