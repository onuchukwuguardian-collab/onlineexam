<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class FixPasswordHashing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-password-hashing {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix password hashing for users with unhashed passwords';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Checking for users with unhashed passwords...');
        
        $users = User::all();
        $fixedCount = 0;
        
        foreach ($users as $user) {
            // Check if password is already hashed (Bcrypt hashes start with $2y$)
            if ($user->password && !str_starts_with($user->password, '$2y$')) {
                if ($dryRun) {
                    $this->line("Would fix password for user: {$user->email} (ID: {$user->id})");
                } else {
                    $user->password = Hash::make($user->password);
                    $user->save();
                    $this->line("Fixed password for user: {$user->email} (ID: {$user->id})");
                }
                $fixedCount++;
            }
        }
        
        if ($fixedCount === 0) {
            $this->info('All user passwords are already properly hashed.');
        } else {
            if ($dryRun) {
                $this->info("Found {$fixedCount} users with unhashed passwords. Run without --dry-run to fix them.");
            } else {
                $this->info("Successfully fixed {$fixedCount} user passwords.");
            }
        }
        
        return 0;
    }
}
