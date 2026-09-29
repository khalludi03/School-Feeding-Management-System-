try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    DB::statement('DROP TABLE IF EXISTS delivery_receipt_item_allocations');
    DB::statement('DROP TABLE IF EXISTS delivery_receipt_item_zero_confirmations');
    DB::statement('DROP TABLE IF EXISTS delivery_receipt_items');
    DB::statement('DROP TABLE IF EXISTS delivery_receipts');
    DB::statement('DROP TABLE IF EXISTS non_working_days');
    DB::statement('DROP TABLE IF EXISTS feeding_item_prices');
    DB::statement('DROP TABLE IF EXISTS feeding_date_item_schedules');
    DB::statement('DROP TABLE IF EXISTS feeding_item_rations');
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
} catch (\Exception $e) {}

try {
    Schema::dropColumns('feeding_items', ['supply_weekdays']);
} catch (\Exception $e) {}

Artisan::call('migrate', ['--force' => true]);
echo Artisan::output();
