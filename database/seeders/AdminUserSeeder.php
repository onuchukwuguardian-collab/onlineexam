<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'], // Unique identifier to check
            [
                'name' => 'Admin User One',
                'password' => Hash::make('password'), // Change this!
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('superpass'), // Change this!
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
