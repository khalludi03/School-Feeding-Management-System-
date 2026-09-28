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
        Schema::create('delivery_receipt_item_zero_confirmations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('feeding_item_id');
            $table->foreign('feeding_item_id', 'dr_zero_conf_item_id_fk')->references('id')->on('feeding_items')->restrictOnDelete();
            $table->text('reason');
            $table->foreignId('confirmed_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['school_id', 'date', 'feeding_item_id'], 'dr_zero_conf_school_date_item_unique');
            $table->index(['school_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_receipt_item_zero_confirmations');
    }
};
