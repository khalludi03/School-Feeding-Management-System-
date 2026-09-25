<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GPSFP_RATION_FACTOR = '0.900';

    public function up(): void
    {
        Schema::table('school_enrolments', function (Blueprint $table): void {
            $table->string('reason', 500)->nullable();
            $table->string('source', 64)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 500)->nullable();
            $table->string('active_key', 10)->nullable();
        });

        $this->assertNoDuplicateActiveDates();

        DB::table('school_enrolments')->update([
            'active_key' => DB::raw('date(effective_on)'),
        ]);

        Schema::table('school_enrolments', function (Blueprint $table): void {
            $table->unique(['school_id', 'active_key'], 'school_enrolments_active_date_unique');
        });

        Schema::table('feeding_cycles', function (Blueprint $table): void {
            $table->decimal('ration_factor', 4, 3)->nullable();
        });

        Schema::table('school_planning_snapshots', function (Blueprint $table): void {
            $table->decimal('ration_factor', 4, 3)->nullable();
        });

        DB::table('feeding_cycles')->whereNull('ration_factor')->update([
            'ration_factor' => self::GPSFP_RATION_FACTOR,
            'updated_at' => now(),
        ]);

        foreach (DB::table('feeding_cycles')->whereNotNull('ration_factor')->get(['id', 'ration_factor']) as $cycle) {
            DB::table('school_planning_snapshots')
                ->where('feeding_cycle_id', $cycle->id)
                ->whereNull('ration_factor')
                ->update(['ration_factor' => $cycle->ration_factor]);
        }
    }

    public function down(): void
    {
        Schema::table('school_planning_snapshots', function (Blueprint $table): void {
            $table->dropColumn('ration_factor');
        });

        Schema::table('feeding_cycles', function (Blueprint $table): void {
            $table->dropColumn('ration_factor');
        });

        Schema::table('school_enrolments', function (Blueprint $table): void {
            $table->dropUnique('school_enrolments_active_date_unique');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['reason', 'source', 'cancelled_at', 'cancellation_reason', 'active_key']);
        });
    }

    /**
     * The active-date unique index cannot be built while two uncancelled rows share a school and date.
     */
    private function assertNoDuplicateActiveDates(): void
    {
        $conflicts = DB::table('school_enrolments')
            ->select(['school_id', DB::raw('date(effective_on) AS day'), DB::raw('COUNT(*) AS duplicate_count')])
            ->groupBy('school_id', DB::raw('date(effective_on)'))
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($conflicts->isEmpty()) {
            return;
        }

        $summary = $conflicts
            ->map(fn (object $row): string => "school {$row->school_id} on {$row->day} ({$row->duplicate_count} rows)")
            ->implode('; ');

        throw new RuntimeException(
            "Cannot enforce one active enrolment count per school and date. Resolve these duplicates first: {$summary}",
        );
    }
};
