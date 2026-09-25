<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_code_sequences', function (Blueprint $table) {
            $table->string('prefix', 8)->primary();
            $table->unsignedBigInteger('next_number');
        });
        DB::table('school_code_sequences')->insert(['prefix' => 'AN', 'next_number' => 1]);

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('bangla_name');
            $table->string('union', 120)->nullable();
            $table->string('cluster', 120)->nullable();
            $table->string('teacher_name')->nullable();
            $table->string('teacher_phone', 20)->nullable();
            $table->string('emis_code', 64)->nullable()->unique();
            $table->string('emis_source')->nullable();
            $table->timestamp('emis_verified_at')->nullable();
            $table->foreignId('emis_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('school_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->date('effective_on');
            $table->unsignedInteger('pupil_count');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'effective_on']);
        });

        Schema::create('school_participation_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['school_id', 'starts_on']);
        });

        Schema::table('audit_events', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('audit_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
        });
        Schema::dropIfExists('school_participation_periods');
        Schema::dropIfExists('school_enrolments');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('school_code_sequences');
    }
};
