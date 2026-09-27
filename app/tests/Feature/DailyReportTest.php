<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
use App\Models\DeliveryReceiptItemAllocation;
use App\Models\DeliveryReceiptItemZeroConfirmation;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\NonWorkingDay;
use App\Models\School;
use App\Models\User;
use App\Services\DailyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use ZipArchive;

class DailyReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_shortfall_is_demand_minus_delivered_per_item(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);

        $this->recordEntry($school, $items, ['bread' => 60, 'egg' => 0, 'banana' => 0]);

        $row = app(DailyReportService::class)->forDate(Carbon::today())['rows'][0];

        $this->assertTrue($row['entry_recorded']);
        $this->assertSame(90, $row['daily_demand']);
        $this->assertSame(90, $row['demand']['bread']);
        $this->assertSame(60, $row['delivered']['bread']);
        $this->assertSame(30, $row['shortfall']['bread'], '60 delivered against 90 demanded leaves 30 short.');
        $this->assertNull($row['shortfall']['egg'], 'Egg demand is unknown, so its shortfall cannot be derived.');
    }

    public function test_excess_delivery_is_reported_as_a_negative_shortfall(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);

        $this->recordEntry($school, $items, ['bread' => 95, 'egg' => 0, 'banana' => 0]);

        $row = app(DailyReportService::class)->forDate(Carbon::today())['rows'][0];

        $this->assertSame(-5, $row['shortfall']['bread']);
    }

    public function test_a_school_with_no_entry_shows_a_full_shortfall(): void
    {
        $cycle = $this->openCycle();
        $this->participatingSchool($cycle, pupils: 100);
        $this->items($cycle);

        $row = app(DailyReportService::class)->forDate(Carbon::today())['rows'][0];

        $this->assertFalse($row['entry_recorded']);
        $this->assertNull($row['delivered']['bread']);
        $this->assertSame(90, $row['shortfall']['bread']);
    }

    public function test_an_item_without_a_supply_pattern_reports_unknown_demand_not_zero(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);

        $this->recordEntry($school, $items, ['bread' => 90, 'egg' => 40, 'banana' => 40]);

        $row = app(DailyReportService::class)->forDate(Carbon::today())['rows'][0];

        $this->assertNull($row['demand']['egg'], 'Egg has no weekday pattern, so demand is unknown.');
        $this->assertNull($row['shortfall']['egg']);
        $this->assertSame(40, $row['delivered']['egg'], 'What was received is still reported.');
    }

    public function test_totals_flag_that_they_are_incomplete_when_any_demand_is_unknown(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);
        $this->recordEntry($school, $items, ['bread' => 90, 'egg' => 0, 'banana' => 0]);

        $totals = app(DailyReportService::class)->forDate(Carbon::today())['totals'];

        $this->assertSame(90, $totals['demand']['bread']);
        $this->assertSame(0, $totals['shortfall']['bread']);
        $this->assertSame(1, $totals['demand_unknown_schools']['egg']);
        $this->assertSame(1, $totals['unknown_demand_schools']);
        $this->assertFalse($totals['complete']);
    }

    public function test_totals_are_complete_when_every_item_has_a_pattern(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);
        $items[1]->forceFill(['supply_weekdays' => [0, 1, 2, 3, 4, 5, 6], 'supply_pattern_source' => 'work_order'])->save();
        $items[2]->forceFill(['supply_weekdays' => [0, 1, 2, 3, 4, 5, 6], 'supply_pattern_source' => 'work_order'])->save();
        $this->recordEntry($school, $items, ['bread' => 90, 'egg' => 88, 'banana' => 91]);

        $report = app(DailyReportService::class)->forDate(Carbon::today());

        $this->assertTrue($report['totals']['complete']);
        $this->assertSame(90, $report['totals']['demand']['egg']);
        $this->assertSame(2, $report['totals']['shortfall']['egg']);
        $this->assertSame(-1, $report['totals']['shortfall']['banana']);
    }

    public function test_a_non_working_day_generates_no_demand_and_hides_school_rows(): void
    {
        $cycle = $this->openCycle();
        $this->participatingSchool($cycle, pupils: 100);
        $this->items($cycle);
        NonWorkingDay::factory()->create(['holiday_on' => today()->toDateString()]);

        $report = app(DailyReportService::class)->forDate(Carbon::today());

        $this->assertFalse($report['is_working_day']);
        $this->assertSame([], $report['rows']);
        $this->assertSame(0, $report['totals']['schools']);
        $this->assertSame(0, $report['totals']['entries_missing']);
    }

    public function test_only_schools_participating_on_the_date_are_reported(): void
    {
        $cycle = $this->openCycle();

        // Codes are fixed because the report sorts rows by code, so the expected order has to be defined.
        $participating = $this->participatingSchool($cycle, pupils: 100, code: 'AN-000001');

        // Deactivated from tomorrow, so it is still in the programme today (US2.4-AC4).
        $closingSoon = $this->participatingSchool($cycle, pupils: 100, code: 'AN-000002');
        $closingSoon->participationPeriods()->update(['ends_on' => today()->toDateString()]);
        $closingSoon->forceFill(['is_active' => false])->save();

        // Participation already ended, so it drops out even though the school is still active.
        $alreadyLeft = $this->participatingSchool($cycle, pupils: 100);
        $alreadyLeft->participationPeriods()->update(['ends_on' => today()->subDay()->toDateString()]);

        $neverJoined = School::factory()->create();
        $this->items($cycle);

        $report = app(DailyReportService::class)->forDate(Carbon::today());

        $ids = array_map(fn ($row) => $row['school']->id, $report['rows']);
        $this->assertSame([$participating->id, $closingSoon->id], $ids);
        $this->assertNotContains($alreadyLeft->id, $ids, 'A closed participation period excludes the school.');
        $this->assertNotContains($neverJoined->id, $ids, 'A school with no participation is never reported.');
        $this->assertSame(2, $report['totals']['schools']);
    }

    public function test_the_report_page_is_available_to_both_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'field_staff']);

        $this->actingAs($admin)->get(route('admin.reports.daily'))->assertOk();
        $this->actingAs($staff)->get(route('field.report'))->assertOk();
    }

    public function test_the_excel_export_is_a_readable_workbook(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);
        $this->recordEntry($school, $items, ['bread' => 90, 'egg' => 0, 'banana' => 0]);

        $response = $this->actingAs($admin)->get(route('admin.reports.daily.export'));

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('Content-Type'),
        );

        $path = tempnam(sys_get_temp_dir(), 'report-test');
        file_put_contents($path, $response->getContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path) === true, 'The export must be a valid zip archive.');

        foreach (['[Content_Types].xml', 'xl/workbook.xml', 'xl/worksheets/sheet1.xml', 'xl/styles.xml'] as $part) {
            $this->assertNotFalse($zip->locateName($part), "Missing {$part} in the workbook.");
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        $this->assertNotFalse(simplexml_load_string($sheet), 'The worksheet must be well-formed XML.');
        $this->assertStringContainsString($school->code, $sheet);
        $this->assertStringContainsString('Upazila total', $sheet);
    }

    public function test_a_company_wide_total_row_cannot_be_confused_with_school_rows(): void
    {
        $cycle = $this->openCycle();
        $this->participatingSchool($cycle, pupils: 100);
        $this->items($cycle);

        $report = app(DailyReportService::class)->forDate(Carbon::today());

        $this->assertSame(0, $report['totals']['entries_recorded']);
        $this->assertSame(1, $report['totals']['entries_missing']);
    }

    public function test_missing_setup_shows_setup_incomplete_banner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = $this->openCycle();
        $this->participatingSchool($cycle, pupils: 100);
        $this->items($cycle);

        $response = $this->actingAs($admin)->get(route('admin.reports.daily', ['date' => today()->toDateString()]));

        $response->assertOk();
        $response->assertSee('Supply weekdays are not configured');
        $response->assertSee('shown as unknown rather than zero');
    }

    public function test_multiple_allocations_to_same_item_are_summed_once(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);
        $staff = User::factory()->create(['role' => 'field_staff']);

        $receiptA = $this->receiptWithChalan($school, 'CH-A', $staff);
        $lineA = DeliveryReceiptItem::factory()->create([
            'delivery_receipt_id' => $receiptA->id,
            'feeding_item_id' => $items[0]->id,
            'delivered_quantity' => 50,
        ]);
        DeliveryReceiptItemAllocation::factory()->create([
            'delivery_receipt_item_id' => $lineA->id,
            'allocation_date' => today()->toDateString(),
            'allocated_quantity' => 50,
        ]);

        $receiptB = $this->receiptWithChalan($school, 'CH-B', $staff);
        $lineB = DeliveryReceiptItem::factory()->create([
            'delivery_receipt_id' => $receiptB->id,
            'feeding_item_id' => $items[0]->id,
            'delivered_quantity' => 40,
        ]);
        DeliveryReceiptItemAllocation::factory()->create([
            'delivery_receipt_item_id' => $lineB->id,
            'allocation_date' => today()->toDateString(),
            'allocated_quantity' => 40,
        ]);

        $row = app(DailyReportService::class)->forDate(today())['rows'][0];

        $this->assertSame(90, $row['delivered']['bread']);
        $this->assertSame(0, $row['shortfall']['bread']);
        $this->assertSame('submitted', $row['status']['bread']);
    }

    public function test_shortage_excess_and_net_balance_totals_are_separated(): void
    {
        $cycle = $this->openCycle();
        $shortageSchool = $this->participatingSchool($cycle, pupils: 100, code: 'AN-000001');
        $excessSchool = $this->participatingSchool($cycle, pupils: 100, code: 'AN-000002');
        $items = $this->items($cycle);

        $this->recordEntry($shortageSchool, $items, ['bread' => 70, 'egg' => 0, 'banana' => 0]);
        $this->recordEntry($excessSchool, $items, ['bread' => 110, 'egg' => 0, 'banana' => 0]);

        $totals = app(DailyReportService::class)->forDate(today())['totals'];

        $this->assertSame(20, $totals['shortage']['bread']);
        $this->assertSame(20, $totals['excess']['bread']);
        $this->assertSame(0, $totals['net_balance']['bread']);
        $this->assertSame(20, $totals['total_shortage']);
        $this->assertSame(20, $totals['total_excess']);
        $this->assertSame(0, $totals['total_net_balance']);
    }

    public function test_net_balance_is_negative_when_shortfall_exceeds_excess(): void
    {
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);

        $this->recordEntry($school, $items, ['bread' => 70, 'egg' => 0, 'banana' => 0]);

        $totals = app(DailyReportService::class)->forDate(Carbon::today())['totals'];

        $this->assertSame(20, $totals['total_shortage']);
        $this->assertSame(0, $totals['total_excess']);
        $this->assertSame(-20, $totals['total_net_balance']);
    }

    public function test_confirmed_shortfall_is_distinguished_from_not_submitted(): void
    {
        $cycle = $this->openCycle();
        $confirmedSchool = $this->participatingSchool($cycle, pupils: 100, code: 'AN-000001');
        $missingSchool = $this->participatingSchool($cycle, pupils: 100, code: 'AN-000002');
        $item = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'bread', 'name' => 'Bread', 'sort_order' => 1, 'supply_days' => 16]);

        $staff = User::factory()->create(['role' => 'field_staff']);
        DeliveryReceiptItemZeroConfirmation::query()->create([
            'school_id' => $confirmedSchool->id,
            'feeding_item_id' => $item->id,
            'date' => today()->toDateString(),
            'reason' => 'No delivery',
            'confirmed_by' => $staff->id,
        ]);

        $rows = collect(app(DailyReportService::class)->forDate(today())['rows'])->keyBy(fn ($r) => $r['school']->code);

        $this->assertSame('confirmed_shortfall', $rows['AN-000001']['status']['bread']);
        $this->assertSame('not_submitted', $rows['AN-000002']['status']['bread']);

        $totals = app(DailyReportService::class)->forDate(today())['totals'];
        $this->assertSame(1, $totals['entries_missing']);
        $this->assertFalse($totals['complete']);
    }

    public function test_future_date_shows_planned_and_no_missing_entries(): void
    {
        $cycle = $this->openCycle();
        $cycle->forceFill(['ends_on' => today()->addDays(30)])->save();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);
        $staff = User::factory()->create(['role' => 'field_staff']);

        $futureDate = today()->addDay();
        $receipt = $this->receiptWithChalan($school, 'CH-FUT', $staff);
        $receipt->delivery_date = today()->toDateString();
        $receipt->save();
        $line = DeliveryReceiptItem::factory()->create([
            'delivery_receipt_id' => $receipt->id,
            'feeding_item_id' => $items[0]->id,
            'delivered_quantity' => 90,
        ]);
        DeliveryReceiptItemAllocation::factory()->create([
            'delivery_receipt_item_id' => $line->id,
            'allocation_date' => $futureDate->toDateString(),
            'allocated_quantity' => 90,
        ]);

        $report = app(DailyReportService::class)->forDate($futureDate);

        $this->assertTrue($report['is_future']);
        $this->assertSame(0, $report['totals']['entries_missing']);
        $this->assertSame('planned', $report['rows'][0]['status']['bread']);
        $this->assertSame(90, $report['rows'][0]['delivered']['bread']);
    }

    public function test_unscheduled_items_do_not_create_missing_entries(): void
    {
        $this->travelTo(Carbon::parse('2026-09-28')); // A Monday
        
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);

        // Configure all items as not scheduled today (Monday).
        foreach ($items as $item) {
            $item->forceFill(['supply_weekdays' => [Carbon::SUNDAY], 'supply_pattern_source' => 'work_order'])->save();
        }

        $report = app(DailyReportService::class)->forDate(today());

        $this->assertSame('not_scheduled', $report['rows'][0]['status']['bread']);
        $this->assertSame('not_scheduled', $report['rows'][0]['status']['egg']);
        $this->assertSame('not_scheduled', $report['rows'][0]['status']['banana']);
        $this->assertSame(0, $report['totals']['entries_missing']);
        $this->assertTrue($report['totals']['complete']);
    }

    public function test_excel_export_includes_status_and_totals(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cycle = $this->openCycle();
        $school = $this->participatingSchool($cycle, pupils: 100);
        $items = $this->items($cycle);
        $this->recordEntry($school, $items, ['bread' => 60, 'egg' => 0, 'banana' => 0]);

        $response = $this->actingAs($admin)->get(route('admin.reports.daily.export', ['date' => today()->toDateString()]));
        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'report-export');
        file_put_contents($path, $response->getContent());
        $zip = new ZipArchive;
        $zip->open($path);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);

        $this->assertStringContainsString('Status', $sheet);
        $this->assertStringContainsString('Shortage', $sheet);
        $this->assertStringContainsString('Upazila total', $sheet);
        $this->assertStringContainsString($school->code, $sheet);
    }

    private function receiptWithChalan(School $school, string $chalan, User $staff): DeliveryReceipt
    {
        return DeliveryReceipt::factory()->create([
            'school_id' => $school->id,
            'delivery_date' => today()->toDateString(),
            'entered_by' => $staff->id,
            'responsible_by' => $staff->id,
            'chalan_number' => $chalan,
            'chalan_date' => today()->toDateString(),
        ]);
    }

    private function openCycle(): FeedingCycle
    {
        $cycle = FeedingCycle::factory()->create([
            'starts_on' => today()->subDays(5)->toDateString(),
            'ends_on' => today()->addDays(20)->toDateString(),
        ]);
        $cycle->forceFill(['ration_factor' => 0.9])->save();

        return $cycle->refresh();
    }

    /**
     * @return list<FeedingItem>
     */
    private function items(FeedingCycle $cycle): array
    {
        $bread = FeedingItem::factory()->for($cycle)->suppliedOn([0, 1, 2, 3, 4, 5, 6])
            ->create(['item_key' => 'bread', 'name' => 'Bread', 'sort_order' => 1, 'supply_days' => 16]);
        $egg = FeedingItem::factory()->for($cycle)->withoutSupplyPattern()
            ->create(['item_key' => 'egg', 'name' => 'Egg', 'sort_order' => 2, 'supply_days' => 12]);
        $banana = FeedingItem::factory()->for($cycle)->withoutSupplyPattern()
            ->create(['item_key' => 'banana', 'name' => 'Banana', 'sort_order' => 3, 'supply_days' => 5]);

        return [$bread, $egg, $banana];
    }

    private function participatingSchool(FeedingCycle $cycle, int $pupils, ?string $code = null): School
    {
        $factory = School::factory();

        if ($code !== null) {
            $factory = $factory->state(['code' => $code]);
        }

        $school = $factory->create();
        $school->participationPeriods()->create(['starts_on' => $cycle->starts_on->toDateString()]);
        $school->enrolments()->create([
            'effective_on' => $cycle->starts_on->toDateString(),
            'pupil_count' => $pupils,
            'source' => 'test',
        ]);

        return $school->refresh();
    }

    /**
     * @param  array<string, int>  $quantities
     */
    private function recordEntry(School $school, array $items, array $quantities): DeliveryReceipt
    {
        $staff = User::factory()->create(['role' => 'field_staff']);

        $receipt = new DeliveryReceipt;
        $receipt->school_id = $school->id;
        $receipt->delivery_date = today()->toDateString();
        $receipt->entered_by = $staff->id;
        $receipt->responsible_by = $staff->id;
        $receipt->save();

        foreach ($items as $item) {
            $quantity = $quantities[$item->item_key] ?? 0;
            $line = new DeliveryReceiptItem;
            $line->delivery_receipt_id = $receipt->id;
            $line->feeding_item_id = $item->id;
            $line->delivered_quantity = $quantity;
            $line->save();

            if ($quantity > 0) {
                DeliveryReceiptItemAllocation::query()->create([
                    'delivery_receipt_item_id' => $line->id,
                    'allocation_date' => today()->toDateString(),
                    'allocated_quantity' => $quantity,
                ]);
            }
        }

        return $receipt->refresh();
    }
}
