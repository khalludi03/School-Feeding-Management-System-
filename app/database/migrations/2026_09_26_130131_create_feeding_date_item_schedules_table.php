<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeding_date_item_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feeding_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feeding_item_id')->constrained()->cascadeOnDelete();
            $table->date('schedule_date');
            $table->boolean('is_scheduled');
            $table->string('source', 30)->default('assumed');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['feeding_cycle_id', 'feeding_item_id', 'schedule_date']);
            $table->index(['feeding_cycle_id', 'schedule_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeding_date_item_schedules');
    }
};
