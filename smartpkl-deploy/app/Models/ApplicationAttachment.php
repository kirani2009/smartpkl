<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationAttachment extends Model
{
    protected $fillable = [
        'application_id',
        'type',
        'title',
        'file_path',
        'original_name',
        'file_size',
        'mime_type',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
