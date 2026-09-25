<?php

namespace Database\Seeders;

use App\Models\AuditEvent;
use App\Models\FeedingCycle;
use App\Models\FeedingItem;
use App\Models\School;
use App\Models\SchoolEnrolment;
use App\Models\SchoolPlanningSnapshot;
use App\Services\GpsfpDataParser;
use App\Services\ItemSupplyPattern;
use App\Services\ProvisionalEmisService;
use App\Services\SchoolCodeService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GpsfpSeptember2026Seeder extends Seeder
{
    private const RATION_FACTOR = '0.900';

    private const BASELINE_EFFECTIVE_ON = '2026-09-01';

    public function run(): void
    {
        $parser = app(GpsfpDataParser::class);
        $codes = app(SchoolCodeService::class);
        $provisionalEmis = app(ProvisionalEmisService::class);
        $schools = $parser->schoolRecords();
        $items = $parser->itemRecords();

        if (count($schools) !== 110 || count(array_unique(array_column($schools, 'serial'))) !== 110) {
            throw new RuntimeException('The GPSFP school import must contain exactly 110 unique rows.');
        }

        DB::transaction(function () use ($codes, $provisionalEmis, $schools, $items): void {
            $cycle = FeedingCycle::query()->updateOrCreate([
                'slug' => 'gpsfp-2026-09',
            ], [
                'title' => 'GPSFP September 2026 feeding cycle',
                'scope' => 'সোনাগাজী, বরুড়া, সুবর্ণচর, রামগতি, আনোয়ারা, বিলাইছড়ি, লক্ষ্মীছড়ি',
                'starts_on' => '2026-09-01',
                'ends_on' => '2026-09-30',
                'tender_id' => '1163652',
                'circular_reference' => '৩৮.০১.০০০০.০১২.০৭.০৫৯.২৫-১০২৩',
                'school_source_file' => $schools[0]['source_file'],
                'item_source_file' => $items[0]['source_file'],
                'regional_daily_quantity' => $items[0]['daily_quantity'],
                'total_value' => $this->totalValue($items),
                'ration_factor' => self::RATION_FACTOR,
            ]);

            foreach ($items as $item) {
                FeedingItem::query()->updateOrCreate([
                    'feeding_cycle_id' => $cycle->id,
                    'item_key' => $item['item_key'],
                ], [
                    'name' => $item['name'],
                    'unit' => $item['unit'],
                    'weight_grams' => $item['weight_grams'],
                    'daily_quantity' => $item['daily_quantity'],
                    'supply_days' => $item['supply_days'],
                    'total_quantity' => $item['total_quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_value' => $item['total_value'],
                    'sort_order' => $item['sort_order'],
                    'supply_weekdays' => $this->supplyWeekdaysFor($item['item_key']),
                    'supply_pattern_source' => $this->supplyWeekdaysFor($item['item_key']) === null
                        ? null
                        : ItemSupplyPattern::SOURCE_WORK_ORDER,
                ]);
            }

            foreach ($schools as $record) {
                $sourceKey = sprintf('gpsfp:anwara:2026-09:%03d', $record['serial']);
                $school = School::query()->where('source_key', $sourceKey)->first();
                if ($school === null) {
                    $school = School::create([
                        'code' => $codes->nextCode(),
                        'source_key' => $sourceKey,
                        'bangla_name' => $record['school_name'],
                        'upazila' => 'আনোয়ারা',
                        'district' => 'চট্টগ্রাম',
                        'teacher_name' => $record['teacher_name'],
                        'teacher_phone' => $record['teacher_phone'],
                        'is_active' => true,
                        'emis_code' => $provisionalEmis->generate(),
                        'emis_source' => 'GPSFP import provisional identity',
                        'emis_is_provisional' => true,
                    ]);
                    AuditEvent::recordSchool('school_provisional_emis_generated', $school, [
                        'source' => 'gpsfp_import',
                        'source_serial' => $record['serial'],
                    ]);
                } elseif ($school->is_active && $school->emis_code === null) {
                    $school->forceFill([
                        'emis_code' => $provisionalEmis->generate(),
                        'emis_source' => 'GPSFP import provisional identity',
                        'emis_is_provisional' => true,
                    ])->save();
                    AuditEvent::recordSchool('school_provisional_emis_generated', $school, [
                        'source' => 'gpsfp_import',
                        'source_serial' => $record['serial'],
                    ]);
                }

                $snapshot = SchoolPlanningSnapshot::query()
                    ->where('feeding_cycle_id', $cycle->id)
                    ->where('school_id', $school->id)
                    ->first();
                $sourceFlags = $record['source_flags'];
                if ($snapshot !== null && $snapshot->source_flags !== null) {
                    foreach ($snapshot->source_flags as $flag => $value) {
                        if ($flag === 'source_drift' && ! isset($sourceFlags['source_drift'])) {
                            $sourceFlags['source_drift'] = $value;
                        }
                    }
                }
                $driftFlags = $this->sourceDriftFlags($snapshot, $record);
                if ($driftFlags !== []) {
                    $sourceFlags['source_drift'] = implode(' ', $driftFlags);
                }

                SchoolPlanningSnapshot::query()->updateOrCreate([
                    'feeding_cycle_id' => $cycle->id,
                    'school_id' => $school->id,
                ], [
                    'source_serial' => $record['serial'],
                    'pupil_count' => $record['pupil_count'],
                    'target_pupil_count' => $record['target_pupil_count'],
                    'daily_demand' => $record['daily_demand'],
                    'bread_quantity' => $record['bread_quantity'],
                    'egg_quantity' => $record['egg_quantity'],
                    'banana_quantity' => $record['banana_quantity'],
                    'source_file' => $record['source_file'],
                    'source_flags' => $sourceFlags ?: null,
                    'source_payload' => $record['source_payload'],
                    'ration_factor' => self::RATION_FACTOR,
                ]);

                $this->recordBaselineEnrolment($school, $record);
            }
        });
    }

    /**
     * The GPSFP roster is the operational starting point for each school, so it becomes one immutable
     * provenance-marked enrolment effective on the first day of the cycle. Re-running the seeder must
     * never rewrite it, because an Admin may already have scheduled a later change.
     */
    private function recordBaselineEnrolment(School $school, array $record): void
    {
        $exists = SchoolEnrolment::query()
            ->where('school_id', $school->id)
            ->whereDate('effective_on', self::BASELINE_EFFECTIVE_ON)
            ->exists();

        if ($exists) {
            return;
        }

        $school->enrolments()->create([
            'effective_on' => self::BASELINE_EFFECTIVE_ON,
            'pupil_count' => $record['pupil_count'],
            'reason' => 'GPSFP September 2026 roster baseline.',
            'source' => 'gpsfp_import',
        ]);
    }

    /**
     * Condition 2 of the September 2026 call-off notice supplies bread for Sunday and Monday demand,
     * and for Wednesday and Thursday demand, so bread is handed over on those four weekdays only.
     * The notice gives totals for egg and banana but never names their weekdays, so those patterns
     * stay unconfigured rather than guessed: delivered quantities are still recorded, but their
     * derived demand is reported as unknown until the Business Rules sheet is available.
     *
     * @return list<int>|null Carbon weekday numbers, 0 = Sunday.
     */
    private function supplyWeekdaysFor(string $itemKey): ?array
    {
        return $itemKey === 'banana_bread'
            ? [Carbon::SUNDAY, Carbon::MONDAY, Carbon::WEDNESDAY, Carbon::THURSDAY]
            : null;
    }

    private function sourceDriftFlags(?SchoolPlanningSnapshot $snapshot, array $record): array
    {
        if ($snapshot === null) {
            return [];
        }

        $fields = [
            'source_serial' => 'serial',
            'pupil_count' => 'pupil_count',
            'target_pupil_count' => 'target_pupil_count',
            'daily_demand' => 'daily_demand',
            'bread_quantity' => 'bread_quantity',
            'egg_quantity' => 'egg_quantity',
            'banana_quantity' => 'banana_quantity',
            'source_file' => 'source_file',
        ];
        $changed = [];
        foreach ($fields as $field => $recordField) {
            if ((string) $snapshot->{$field} !== (string) $record[$recordField]) {
                $changed[] = $field;
            }
        }

        if (($snapshot->source_payload['raw_row'] ?? null) !== ($record['source_payload']['raw_row'] ?? null)) {
            $changed[] = 'source_payload';
        }

        return $changed === [] ? [] : ['The source row changed for: '.implode(', ', array_unique($changed)).'.'];
    }

    private function totalValue(array $items): float
    {
        $total = array_sum(array_map(
            static fn (array $item): float => (float) $item['total_value'],
            $items,
        ));

        return round($total, 2);
    }
}
