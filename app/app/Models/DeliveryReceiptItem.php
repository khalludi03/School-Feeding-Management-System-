<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryReceiptItem extends Model
{
    protected $fillable = ['delivered_quantity'];

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
}
