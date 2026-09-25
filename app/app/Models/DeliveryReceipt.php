<?php

namespace App\Models;

use Database\Factories\DeliveryReceiptFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * What one school actually received on one date. The school, date and author are fixed at creation
 * so a correction can only ever restate quantities, never move an entry to another school or day.
 */
class DeliveryReceipt extends Model
{
    /** @use HasFactory<DeliveryReceiptFactory> */
    use HasFactory;

    protected $fillable = ['chalan_photo_path', 'notes'];

    protected function casts(): array
    {
        return ['delivery_date' => 'date'];
    }

    protected static function booted(): void
    {
        static::updating(function (DeliveryReceipt $receipt): void {
            if ($receipt->isDirty(['school_id', 'delivery_date', 'entered_by'])) {
                throw new LogicException(
                    'A delivery entry is tied to one school and one date. Enter a new entry instead of moving this one.',
                );
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryReceiptItem::class);
    }

    /**
     * @return array<string, int>
     */
    public function quantitiesByItemKey(): array
    {
        return $this->items
            ->loadMissing('item')
            ->filter(fn (DeliveryReceiptItem $line): bool => $line->item !== null)
            ->mapWithKeys(fn (DeliveryReceiptItem $line): array => [$line->item->item_key => $line->delivered_quantity])
            ->all();
    }
}
