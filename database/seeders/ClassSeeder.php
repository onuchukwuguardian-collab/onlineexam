<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ClassModel; // Your Class model
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    public function run(): void
    {
        // To avoid issues if seeder is run multiple times
        DB::table('classes')->delete(); // Clear existing data first (optional)

        $classes = [
            ['name' => 'JSS1', 'level_group' => 'JSS', 'description' => 'Junior Secondary School 1'],
            ['name' => 'JSS2', 'level_group' => 'JSS', 'description' => 'Junior Secondary School 2'],
            ['name' => 'JSS3', 'level_group' => 'JSS', 'description' => 'Junior Secondary School 3'],
            ['name' => 'SS1', 'level_group' => 'SS', 'description' => 'Senior Secondary School 1'],
            ['name' => 'SS2', 'level_group' => 'SS', 'description' => 'Senior Secondary School 2'],
            ['name' => 'SS3', 'level_group' => 'SS', 'description' => 'Senior Secondary School 3'],
            ['name' => 'SYSTEM CLASS', 'level_group' => 'SYSTEM', 'description' => 'For system use, unassigned items'],
        ];

        foreach ($classes as $classData) {
            ClassModel::firstOrCreate(['name' => $classData['name']], $classData);
        }
    }
}
