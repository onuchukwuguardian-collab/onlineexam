<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ClassSeeder::class,         // Create classes first
            AdminUserSeeder::class,     // Create admin user(s)
            UserSeeder::class,          // Create student users (needs classes)
            SubjectSeeder::class,       // Create subjects (needs classes)
            QuestionSeeder::class,      // Create questions (needs subjects)
        ]);
    }
}
