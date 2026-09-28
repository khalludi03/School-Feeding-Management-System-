<?php
use App\Models\FeedingCycle;
use App\Models\User;
use Carbon\Carbon;
use App\Services\DateItemScheduleService;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cycle = FeedingCycle::first();
$items = $cycle->items()->get();
$from = Carbon::parse('2026-09-01');
$to = $from->copy()->endOfMonth();

$dateItems = [];
for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
    foreach ($items as $item) {
        $isWeekend = $date->isFriday() || $date->isSaturday();
        $dateItems[] = [
            'feeding_item_id' => $item->id,
            'is_scheduled' => !$isWeekend, // schedule all items on weekdays
            'schedule_date' => $date->toDateString(),
        ];
    }
}

app(DateItemScheduleService::class)->upsertMonth($cycle, $from, $dateItems, 'admin', User::first()->id);
echo "Schedules generated: " . \App\Models\DateItemSchedule::count() . "\n";
