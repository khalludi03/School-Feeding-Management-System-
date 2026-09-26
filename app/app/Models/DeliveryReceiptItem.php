<?php

namespace App\Models;

use Database\Factories\DeliveryReceiptItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryReceiptItem extends Model
{
    /** @use HasFactory<DeliveryReceiptItemFactory> */
    use HasFactory;

    protected $fillable = ['delivery_receipt_id', 'feeding_item_id', 'delivered_quantity'];

    protected function casts(): array
    {
        return ['delivered_quantity' => 'integer'];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(DeliveryReceipt::class, 'delivery_receipt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(FeedingItem::class, 'feeding_item_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(DeliveryReceiptItemAllocation::class);
    }
}
