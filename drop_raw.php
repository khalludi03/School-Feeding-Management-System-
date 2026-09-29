<?php
$pdo = DB::connection()->getPdo();
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$pdo->exec('DROP TABLE IF EXISTS delivery_receipt_item_allocations');
$pdo->exec('DROP TABLE IF EXISTS delivery_receipt_item_zero_confirmations');
$pdo->exec('DROP TABLE IF EXISTS delivery_receipt_items');
$pdo->exec('DROP TABLE IF EXISTS delivery_receipts');
$pdo->exec('DROP TABLE IF EXISTS non_working_days');
$pdo->exec('DROP TABLE IF EXISTS feeding_item_prices');
$pdo->exec('DROP TABLE IF EXISTS feeding_date_item_schedules');
$pdo->exec('DROP TABLE IF EXISTS feeding_item_rations');
$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
echo "Done pure PDO\n";
