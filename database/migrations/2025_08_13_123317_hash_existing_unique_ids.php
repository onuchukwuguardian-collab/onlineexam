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
        // Hash existing unique_id values that are stored in plain text
        $users = \App\Models\User::whereNotNull('unique_id')->get();
        
        foreach ($users as $user) {
            // Check if unique_id is already hashed (starts with $2y$ for bcrypt)
            if (!str_starts_with($user->unique_id, '$2y$')) {
                $plainTextPasscode = $user->unique_id;
                $user->unique_id = \Illuminate\Support\Facades\Hash::make($plainTextPasscode);
                $user->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse hash operation - this is intentional for security
        // If you need to rollback, you'll need to manually reset passcodes
    }
};
