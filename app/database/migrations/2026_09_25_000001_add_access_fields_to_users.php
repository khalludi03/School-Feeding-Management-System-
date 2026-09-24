<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('username', 32)->nullable()->unique();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('role', 20)->default('field_staff');
            $table->boolean('is_active')->default(true);
            $table->boolean('must_change_password')->default(false);
            $table->timestamp('temporary_password_expires_at')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->unsignedInteger('auth_version')->default(1);
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'whatsapp_number', 'role', 'is_active', 'must_change_password', 'temporary_password_expires_at', 'is_demo', 'auth_version']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
