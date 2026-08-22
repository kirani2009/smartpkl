<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PHASE 3 — Database Core.
 * Profil guru (user role teacher) yang terikat ke satu sekolah.
 */
class Teacher extends Model
{
    protected $fillable = [
        'user_id',
        'school_id',
        'nip',
        'position',
        'phone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
