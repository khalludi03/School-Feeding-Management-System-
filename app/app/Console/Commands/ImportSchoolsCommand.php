<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\GpsfpDataParser;
use App\Services\SchoolCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSchoolsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sfp:import-schools';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import real school data from the markdown source files in data/ into the schools table';

    /**
     * Execute the console command.
     */
    public function handle(GpsfpDataParser $parser, SchoolCodeService $codes): void
    {
        $this->info('Starting school data import...');

        $schoolsData = $parser->schoolRecords();
        $emisData = $parser->emisRecords();

        $emisBySerial = [];
        foreach ($emisData as $emis) {
            $emisBySerial[$emis['serial']] = $emis;
        }

        $importedCount = 0;
        $updatedCount = 0;

        DB::transaction(function () use ($schoolsData, $emisBySerial, $codes, &$importedCount, &$updatedCount): void {
            foreach ($schoolsData as $record) {
                $serial = $record['serial'];
                $sourceKey = sprintf('gpsfp:anwara:2026-09:%03d', $serial);

                $emisRow = $emisBySerial[$serial] ?? null;
                $emisCode = $emisRow ? $emisRow['emis_code'] : null;

                $school = School::query()->where('source_key', $sourceKey)->first();

                if ($school === null) {
                    // It's a new school
                    $school = School::create([
                        'code' => $codes->nextCode(),
                        'source_key' => $sourceKey,
                        'bangla_name' => $emisRow ? $emisRow['school_name'] : $record['school_name'],
                        'teacher_name' => $record['teacher_name'],
                        'teacher_phone' => $record['teacher_phone'],
                        'emis_code' => $emisCode,
                        'emis_source' => $emisCode ? 'official_list' : null,
                        'emis_verified_at' => $emisCode ? now() : null,
                        'emis_verified_by' => null,
                        'is_active' => true,
                    ]);

                    // Add an initial participation period
                    $school->participationPeriods()->create([
                        'starts_on' => '2026-09-01',
                    ]);

                    // Add the initial enrolment snapshot
                    $school->enrolments()->create([
                        'pupil_count' => $record['pupil_count'],
                        'effective_on' => '2026-09-01',
                    ]);

                    $importedCount++;
                } else {
                    // Update existing school
                    $school->update([
                        'bangla_name' => $emisRow ? $emisRow['school_name'] : $record['school_name'],
                        'teacher_name' => $record['teacher_name'],
                        'teacher_phone' => $record['teacher_phone'],
                        'emis_code' => $emisCode,
                        'emis_source' => $emisCode ? 'official_list' : $school->emis_source,
                        'emis_verified_at' => $emisCode ? ($school->emis_verified_at ?? now()) : $school->emis_verified_at,
                    ]);

                    // Keep existing participation/enrolment or update if needed
                    $updatedCount++;
                }
            }
        });

        $this->info("Import completed successfully. Inserted: {$importedCount}, Updated: {$updatedCount}.");
    }
}
