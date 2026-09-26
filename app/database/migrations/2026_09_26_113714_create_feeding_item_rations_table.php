<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feeding_item_rations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feeding_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feeding_item_id')->constrained()->cascadeOnDelete();
            $table->decimal('ration_factor', 4, 3);
            $table->date('effective_on');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['feeding_cycle_id', 'feeding_item_id', 'effective_on']);
            $table->index(['feeding_cycle_id', 'feeding_item_id', 'effective_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feeding_item_rations');
    }
};
