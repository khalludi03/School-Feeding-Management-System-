<?php
Schema::withoutForeignKeyConstraints(function () {
    Schema::dropIfExists('delivery_receipt_item_allocations');
    Schema::dropIfExists('delivery_receipt_item_zero_confirmations');
    Schema::dropIfExists('delivery_receipt_items');
    Schema::dropIfExists('delivery_receipts');
    Schema::dropIfExists('non_working_days');
});
echo "Dropped\n";
