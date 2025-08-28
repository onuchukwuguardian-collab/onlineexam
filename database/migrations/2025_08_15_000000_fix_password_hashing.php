<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all users with potentially unhashed passwords
        $users = DB::table('users')->get();
        
        foreach ($users as $user) {
            // Check if password is already hashed (Bcrypt hashes start with $2y$)
            if ($user->password && !str_starts_with($user->password, '$2y$')) {
                // Hash the plain text password
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'password' => Hash::make($user->password),
                        'updated_at' => now()
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as we can't unhash passwords
        // If you need to rollback, you'll need to reset passwords manually
    }
};