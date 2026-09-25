<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('emis_is_provisional')->default(false);
        });

        $used = DB::table('schools')
            ->whereNotNull('emis_code')
            ->pluck('emis_code')
            ->map(static fn (mixed $code): string => (string) $code)
            ->all();

        DB::table('schools')
            ->where('is_active', true)
            ->whereNull('emis_code')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $school) use (&$used): void {
                do {
                    $code = (string) random_int(10_000_000_000, 99_999_999_999);
                } while (in_array($code, $used, true));

                $used[] = $code;
                DB::table('schools')->where('id', $school->id)->update([
                    'emis_code' => $code,
                    'emis_is_provisional' => true,
                    'emis_source' => 'Backfilled provisional identity',
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn(['is_active', 'emis_is_provisional']);
        });
    }
};
