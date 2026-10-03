<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Replace all sensitive mobile numbers with a dummy number.
        DB::table('users')
            ->whereNotNull('whatsapp_number')
            ->where('whatsapp_number', '!=', '8801700000000') // Don't replace already dummy numbers
            ->update(['whatsapp_number' => '8801700000000']);
            
        // Also just in case, clean up any schools or invoices that might have accidentally saved real ones.
        if (\Illuminate\Support\Facades\Schema::hasTable('schools')) {
            DB::table('schools')->whereNotNull('teacher_phone')->update(['teacher_phone' => null]);
        }
        
        if (\Illuminate\Support\Facades\Schema::hasTable('form10_invoices')) {
            DB::table('form10_invoices')->whereNotNull('upeo_mobile')->update(['upeo_mobile' => null]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration
    }
};
