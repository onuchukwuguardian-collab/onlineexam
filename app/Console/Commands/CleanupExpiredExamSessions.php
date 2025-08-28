<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExamSession;
use Illuminate\Support\Facades\Log;

class CleanupExpiredExamSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exam:cleanup-sessions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and mark expired exam sessions as completed.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of expired exam sessions...');
        Log::info('Scheduled Task: Starting cleanup of expired exam sessions.');

        $expiredSessions = ExamSession::where('is_active', true)
            ->where('expires_at', '<', now())
            ->get();

        if ($expiredSessions->isEmpty()) {
            $this->info('No expired sessions found to clean up.');
            Log::info('Scheduled Task: No expired sessions found.');
            return 0;
        }

        $this->info("Found {$expiredSessions->count()} expired sessions to clean up.");
        Log::info("Scheduled Task: Found {$expiredSessions->count()} expired sessions to clean up.");

        $cleanedCount = 0;
        foreach ($expiredSessions as $session) {
            // The isExpired check is redundant due to the query, but good for safety
            if ($session->isExpired()) {
                $session->markAsCompleted(true); // true indicates it was auto-completed
                $this->line(" - Cleaned session for User ID: {$session->user_id}, Subject ID: {$session->subject_id}");
                $cleanedCount++;
            }
        }

        $this->info("Successfully cleaned up {$cleanedCount} expired sessions.");
        Log::info("Scheduled Task: Successfully cleaned up {$cleanedCount} expired sessions.");

        return 0;
    }
}
