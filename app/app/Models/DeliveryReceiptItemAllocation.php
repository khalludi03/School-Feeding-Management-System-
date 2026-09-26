<?php

namespace App\Models;

use Database\Factories\DeliveryReceiptItemAllocationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryReceiptItemAllocation extends Model
{
    /** @use HasFactory<DeliveryReceiptItemAllocationFactory> */
    use HasFactory;

    protected $fillable = [
        'delivery_receipt_item_id',
        'allocation_date',
        'allocated_quantity',
    ];

    protected function casts(): array
    {
        return [
            'allocation_date' => 'date',
            'allocated_quantity' => 'integer',
        ];
    }

    public function receiptItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryReceiptItem::class, 'delivery_receipt_item_id');
    }
}
