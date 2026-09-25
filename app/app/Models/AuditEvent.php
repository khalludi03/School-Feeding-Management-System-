<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_id', 'target_user_id', 'school_id', 'action', 'details', 'ip_address'];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public static function record(string $action, ?User $target = null, array $details = []): void
    {
        self::create([
            'actor_id' => auth()->id(),
            'target_user_id' => $target?->id,
            'action' => $action,
            'details' => $details ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    public static function recordSchool(string $action, School $school, array $details = []): void
    {
        self::create([
            'actor_id' => auth()->id(),
            'school_id' => $school->id,
            'action' => $action,
            'details' => $details ?: null,
            'ip_address' => request()->ip(),
        ]);
    }
}
