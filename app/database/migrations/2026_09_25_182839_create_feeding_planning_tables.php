<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('source_key', 100)->nullable()->unique();
            $table->string('upazila', 120)->nullable();
            $table->string('district', 120)->nullable();
        });

        Schema::create('feeding_cycles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title', 160);
            $table->text('scope');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('tender_id', 64)->nullable();
            $table->string('circular_reference', 160)->nullable();
            $table->string('school_source_file', 255);
            $table->string('item_source_file', 255);
            $table->unsignedInteger('regional_daily_quantity')->nullable();
            $table->decimal('total_value', 16, 2);
            $table->timestamps();
        });

        Schema::create('feeding_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feeding_cycle_id')->constrained('feeding_cycles')->cascadeOnDelete();
            $table->string('item_key', 64);
            $table->string('name', 160);
            $table->string('unit', 32);
            $table->unsignedInteger('weight_grams')->nullable();
            $table->unsignedInteger('daily_quantity')->nullable();
            $table->unsignedSmallInteger('supply_days')->nullable();
            $table->unsignedInteger('total_quantity');
            $table->decimal('unit_price', 12, 3);
            $table->decimal('total_value', 16, 2);
            $table->unsignedSmallInteger('sort_order');
            $table->timestamps();
            $table->unique(['feeding_cycle_id', 'item_key']);
        });

        Schema::create('school_planning_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('feeding_cycle_id')->constrained('feeding_cycles')->cascadeOnDelete();
            $table->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $table->unsignedSmallInteger('source_serial');
            $table->unsignedInteger('pupil_count');
            $table->decimal('target_pupil_count', 8, 1);
            $table->unsignedInteger('daily_demand');
            $table->unsignedInteger('bread_quantity');
            $table->unsignedInteger('egg_quantity');
            $table->unsignedInteger('banana_quantity');
            $table->string('source_file', 255);
            $table->json('source_flags')->nullable();
            $table->json('source_payload')->nullable();
            $table->timestamps();
            $table->unique(['feeding_cycle_id', 'school_id']);
            $table->unique(['feeding_cycle_id', 'source_serial']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_planning_snapshots');
        Schema::dropIfExists('feeding_items');
        Schema::dropIfExists('feeding_cycles');

        Schema::table('schools', function (Blueprint $table): void {
            $table->dropUnique(['source_key']);
            $table->dropColumn(['source_key', 'upazila', 'district']);
        });
    }
};
