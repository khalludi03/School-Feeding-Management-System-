<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolEnrolment extends Model
{
    protected $fillable = ['effective_on', 'pupil_count', 'recorded_by'];

    protected function casts(): array
    {
        return ['effective_on' => 'date', 'pupil_count' => 'integer'];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
