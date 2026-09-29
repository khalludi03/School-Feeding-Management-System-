import fs from 'fs';
import path from 'path';

function replaceInFile(file, regex, replacement) {
    const content = fs.readFileSync(file, 'utf-8');
    fs.writeFileSync(file, content.replace(regex, replacement));
}

const dir = 'app/database/migrations/';

// feeding_item_prices
replaceInFile(
    dir + '2026_09_26_123318_create_feeding_item_prices_table.php',
    /->unique\(\['feeding_cycle_id', 'feeding_item_id', 'effective_on'\]\);/g,
    "->unique(['feeding_cycle_id', 'feeding_item_id', 'effective_on'], 'f_item_prices_cycle_item_date_unique');"
);

// feeding_date_item_schedules
replaceInFile(
    dir + '2026_09_26_130131_create_feeding_date_item_schedules_table.php',
    /->unique\(\['feeding_cycle_id', 'feeding_item_id', 'schedule_date'\]\);/g,
    "->unique(['feeding_cycle_id', 'feeding_item_id', 'schedule_date'], 'f_schedules_cycle_item_date_unique');"
);

// feeding_item_rations
replaceInFile(
    dir + '2026_09_26_113714_create_feeding_item_rations_table.php',
    /->unique\(\['feeding_cycle_id', 'feeding_item_id', 'effective_on'\]\);/g,
    "->unique(['feeding_cycle_id', 'feeding_item_id', 'effective_on'], 'f_rations_cycle_item_date_unique');"
);

// delivery_receipt_item_zero_confirmations
replaceInFile(
    dir + '2026_09_26_151134_create_delivery_receipt_item_zero_confirmations_table.php',
    /->unique\(\['school_id', 'date', 'feeding_item_id'\]\);/g,
    "->unique(['school_id', 'date', 'feeding_item_id'], 'dr_zero_conf_school_date_item_unique');"
);

// delivery_receipt_items
replaceInFile(
    dir + '2026_09_25_223454_create_calendar_and_delivery_receipts_tables.php',
    /->unique\(\['delivery_receipt_id', 'feeding_item_id'\]\);/g,
    "->unique(['delivery_receipt_id', 'feeding_item_id'], 'dr_items_receipt_item_unique');"
);

// delivery_receipt_item_allocations
replaceInFile(
    dir + '2026_09_26_151133_create_delivery_receipt_item_allocations_table.php',
    /->unique\(\['delivery_receipt_item_id', 'allocation_date'\]\);/g,
    "->unique(['delivery_receipt_item_id', 'allocation_date'], 'dr_allocations_item_date_unique');"
);

