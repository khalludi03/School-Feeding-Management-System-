<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The official EMIS list supplied every participating school with a real 11-digit code, so the generated
 * placeholder identity has nothing left to do. The flag only ever recorded "this code was invented".
 *
 * Dropping the flag alone would be a lie: every generated code would silently start presenting itself as a
 * verified official one. So invented codes are cleared first, leaving the Admin to enter the real code, and
 * the GPSFP seeder then restores the official code for all 110 imported schools.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('schools')
            ->where('emis_source', 'like', '%provisional%')
            ->update([
                'emis_code' => null,
                'emis_source' => null,
                'emis_verified_at' => null,
                'emis_verified_by' => null,
                'updated_at' => now(),
            ]);

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropColumn('emis_is_provisional');
        });
    }

    public function down(): void
    {
        // The column carried no information beyond its own default, so it comes back empty rather than
        // guessing which schools had held a generated code. Cleared codes are not restored, because the
        // invented values they held are not recoverable.
        Schema::table('schools', function (Blueprint $table): void {
            $table->boolean('emis_is_provisional')->default(false);
        });
    }
};
