<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('non_working_days', function (Blueprint $table): void {
            $table->id();
            $table->date('holiday_on')->unique();
            $table->string('kind', 20);
            $table->string('name', 160);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['kind', 'holiday_on']);
        });

        Schema::table('feeding_items', function (Blueprint $table): void {
            $table->json('supply_weekdays')->nullable();
            $table->string('supply_pattern_source', 40)->nullable();
        });

        Schema::create('delivery_receipts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->date('delivery_date');
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->string('chalan_photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'delivery_date']);
            $table->index('delivery_date');
        });

        Schema::create('delivery_receipt_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feeding_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('delivered_quantity');
            $table->timestamps();

            $table->unique(['delivery_receipt_id', 'feeding_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_receipt_items');
        Schema::dropIfExists('delivery_receipts');

        Schema::table('feeding_items', function (Blueprint $table): void {
            $table->dropColumn(['supply_weekdays', 'supply_pattern_source']);
        });

        Schema::dropIfExists('non_working_days');
    }
};
