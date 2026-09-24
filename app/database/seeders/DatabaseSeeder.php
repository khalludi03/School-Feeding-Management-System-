<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $initial = config('sfp.initial_admin');
        if (blank($initial['username']) || blank($initial['password'])) {
            throw new \RuntimeException('Set SFP_ADMIN_USERNAME and SFP_ADMIN_PASSWORD before seeding.');
        }

        User::firstOrCreate(['username' => Str::lower($initial['username'])], [
            'name' => $initial['name'],
            'role' => 'admin',
            'password' => $initial['password'],
            'is_active' => true,
            'must_change_password' => true,
            'temporary_password_expires_at' => now()->addHours(48),
        ]);

        if (config('sfp.demo.enabled')) {
            $demo = config('sfp.demo');
            foreach (['admin_username', 'admin_password', 'staff_username', 'staff_password', 'staff_whatsapp'] as $key) {
                if (blank($demo[$key])) {
                    throw new \RuntimeException('Set SFP_DEMO_'.strtoupper($key).' before seeding demo users.');
                }
            }

            User::firstOrCreate(['username' => Str::lower($demo['admin_username'])], [
                'name' => 'Demo Admin', 'role' => 'admin', 'password' => $demo['admin_password'],
                'is_demo' => true, 'is_active' => true,
            ]);
            User::firstOrCreate(['username' => Str::lower($demo['staff_username'])], [
                'name' => 'Demo Field Staff', 'role' => 'field_staff',
                'whatsapp_number' => $demo['staff_whatsapp'], 'password' => $demo['staff_password'],
                'is_demo' => true, 'is_active' => true,
            ]);
        }
    }
}
