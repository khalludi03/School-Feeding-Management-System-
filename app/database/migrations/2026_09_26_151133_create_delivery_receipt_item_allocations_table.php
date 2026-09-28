<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('delivery_receipt_item_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_receipt_item_id')->constrained()->cascadeOnDelete();
            $table->date('allocation_date');
            $table->unsignedInteger('allocated_quantity');
            $table->timestamps();

            $table->unique(['delivery_receipt_item_id', 'allocation_date'], 'dr_allocations_item_date_unique');
            $table->index('allocation_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_receipt_item_allocations');
    }
};
