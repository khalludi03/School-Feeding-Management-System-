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
        Schema::table('delivery_receipts', function (Blueprint $table): void {
            $table->string('chalan_number')->nullable()->after('delivery_date');
            $table->date('chalan_date')->nullable()->after('chalan_number');
            $table->foreignId('responsible_by')->nullable()->after('entered_by')->constrained('users')->restrictOnDelete();

            $table->index('school_id');
            $table->dropUnique(['school_id', 'delivery_date']);
            $table->unique(['school_id', 'chalan_number', 'chalan_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('delivery_receipts', function (Blueprint $table): void {
            $table->dropForeign(['responsible_by']);
            $table->dropColumn(['chalan_number', 'chalan_date', 'responsible_by']);

            $table->dropUnique(['school_id', 'chalan_number', 'chalan_date']);
            $table->unique(['school_id', 'delivery_date']);
        });
    }
};
