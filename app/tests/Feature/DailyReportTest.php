<?php

namespace Tests\Feature;

use App\Models\DeliveryReceipt;
use App\Models\DeliveryReceiptItem;
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

    public function test_a_non_working_day_generates_no_demand(): void
    {
        $cycle = $this->openCycle();
        $this->participatingSchool($cycle, pupils: 100);
        $this->items($cycle);
        NonWorkingDay::factory()->create(['holiday_on' => today()->toDateString()]);

        $report = app(DailyReportService::class)->forDate(Carbon::today());

        $this->assertFalse($report['is_working_day']);
        $this->assertNull($report['rows'][0]['daily_demand']);
    }

    public function test_only_schools_participating_on_the_date_are_reported(): void
    {
        $cycle = $this->openCycle();
        $participating = $this->participatingSchool($cycle, pupils: 100);

        // Deactivated from tomorrow, so it is still in the programme today (US2.4-AC4).
        $closingSoon = $this->participatingSchool($cycle, pupils: 100);
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

    private function participatingSchool(FeedingCycle $cycle, int $pupils): School
    {
        $school = School::factory()->create();
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
        $receipt = new DeliveryReceipt;
        $receipt->school_id = $school->id;
        $receipt->delivery_date = today()->toDateString();
        $receipt->entered_by = User::factory()->create(['role' => 'field_staff'])->id;
        $receipt->save();

        foreach ($items as $item) {
            $line = new DeliveryReceiptItem;
            $line->delivery_receipt_id = $receipt->id;
            $line->feeding_item_id = $item->id;
            $line->delivered_quantity = $quantities[$item->item_key] ?? 0;
            $line->save();
        }

        return $receipt->refresh();
    }
}
