<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StaffUserSeeder extends Seeder
{
    /**
     * Seed the staff admin user for the staff Blade pages. The
     * email must be listed in ADMIN_EMAILS to pass staff checks.
     */
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => 'staff@admin.com'],
            ['name' => 'Staff', 'password' => Hash::make('password')],
        );
    }
}
