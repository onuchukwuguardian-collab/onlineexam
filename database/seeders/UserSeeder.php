<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ClassModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str; // For registration number generation

class UserSeeder extends Seeder
{
    // Helper from UserController (or move to a Trait/Service if used in multiple places)
    private function generateUniqueRegistrationNumber($classId, $tryCount = 0)
    {
        $classModel = ClassModel::find($classId);
        if (!$classModel)
            return 'ERR' . Str::random(5);

        $prefix = (strtoupper($classModel->level_group) === 'JSS') ? '5' : '2';

        $lastUser = User::where('registration_number', 'like', $prefix . '%')
            ->selectRaw('CAST(REGEXP_SUBSTR(registration_number, "[0-9]+") AS UNSIGNED) as numeric_suffix_val')
            ->orderBy('numeric_suffix_val', 'desc')
            ->first();

        $nextNumericPart = $lastUser && $lastUser->numeric_suffix_val ? $lastUser->numeric_suffix_val + 1 : 1;

        $regNo = $prefix . str_pad($nextNumericPart, 4, '0', STR_PAD_LEFT);

        if ($tryCount > 0) {
            $regNo .= Str::upper(Str::random(1)); // Add a random char to attempt to resolve collision
        }

        if (User::where('registration_number', $regNo)->exists() && $tryCount < 5) {
            return $this->generateUniqueRegistrationNumber($classId, $tryCount + 1); // Recurse with incremented tryCount
        } elseif (User::where('registration_number', $regNo)->exists()) {
            return $prefix . time() . Str::upper(Str::random(2)); // Final fallback
        }

        return $regNo;
    }


    public function run(): void
    {
        $jss1 = ClassModel::where('name', 'JSS1')->first();
        $jss2 = ClassModel::where('name', 'JSS2')->first();
        $ss1 = ClassModel::where('name', 'SS1')->first();
        $ss2 = ClassModel::where('name', 'SS2')->first();

        $students = [];

        if ($jss1) {
            $students[] = ['name' => 'Adebayo John', 'email' => 'john.ade@example.com', 'class_id' => $jss1->id, 'unique_id' => 'JSS1PASS001'];
            $students[] = ['name' => 'Chidinma Eze', 'email' => 'chidi.eze@example.com', 'class_id' => $jss1->id, 'unique_id' => 'JSS1PASS002'];
        }
        if ($jss2) {
            $students[] = ['name' => 'Musa Aliyu', 'email' => 'musa.aliyu@example.com', 'class_id' => $jss2->id, 'unique_id' => 'JSS2PASS001'];
        }
        if ($ss1) {
            $students[] = ['name' => 'Fatima Bello', 'email' => 'fatima.bello@example.com', 'class_id' => $ss1->id, 'unique_id' => 'SS1PASS001'];
            $students[] = ['name' => 'Emeka Nwosu', 'email' => 'emeka.nwosu@example.com', 'class_id' => $ss1->id, 'unique_id' => 'SS1PASS002'];
        }
        if ($ss2) {
            $students[] = ['name' => 'Femi Uche', 'email' => 'femi.uche@example.com', 'class_id' => $ss2->id, 'unique_id' => 'SS2PASSFEMI'];
            $students[] = ['name' => 'Zainab Idris', 'email' => 'zainab.idris@example.com', 'class_id' => $ss2->id, 'unique_id' => 'SS2PASSZAINAB'];
        }


        foreach ($students as $studentData) {
            User::firstOrCreate(
                ['email' => $studentData['email']], // Check by email
                [
                    'name' => $studentData['name'],
                    'password' => Hash::make('password'), // Default password for all test students
                    'role' => 'user',
                    'class_id' => $studentData['class_id'],
                    'registration_number' => $this->generateUniqueRegistrationNumber($studentData['class_id']),
                    'unique_id' => Hash::make($studentData['unique_id']), // Hashed School Passcode
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
