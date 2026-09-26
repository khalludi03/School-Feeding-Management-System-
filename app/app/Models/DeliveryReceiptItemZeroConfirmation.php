<?php

namespace App\Models;

use Database\Factories\DeliveryReceiptItemZeroConfirmationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryReceiptItemZeroConfirmation extends Model
{
    /** @use HasFactory<DeliveryReceiptItemZeroConfirmationFactory> */
    use HasFactory;

    protected $fillable = [
        'school_id',
        'date',
        'feeding_item_id',
        'reason',
        'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(FeedingItem::class, 'feeding_item_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
