<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\ExamSession;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean up any duplicate records that might violate the unique constraint
        $this->cleanupDuplicateRecords();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as it only cleans up data
    }

    /**
     * Clean up duplicate exam session records that violate the unique constraint
     */
    private function cleanupDuplicateRecords(): void
    {
        // Find all combinations that have duplicates
        $duplicates = DB::select("
            SELECT user_id, subject_id, is_active, COUNT(*) as count
            FROM exam_sessions 
            GROUP BY user_id, subject_id, is_active 
            HAVING COUNT(*) > 1
        ");

        foreach ($duplicates as $duplicate) {
            $userId = $duplicate->user_id;
            $subjectId = $duplicate->subject_id;
            $isActive = $duplicate->is_active;
            
            // Get all sessions for this combination
            $sessions = ExamSession::where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->where('is_active', $isActive)
                ->orderBy('created_at', 'desc')
                ->get();
                
            // Keep the newest one, delete the rest
            $keepSession = $sessions->first();
            $sessionsToDelete = $sessions->skip(1);
            
            foreach ($sessionsToDelete as $session) {
                $session->delete();
            }
            
            $deletedCount = $sessionsToDelete->count();
            if ($deletedCount > 0) {
                echo "Cleaned up {$deletedCount} duplicate session(s) for user {$userId}, subject {$subjectId}, is_active={$isActive}\n";
            }
        }
        
        // Also clean up any orphaned inactive sessions that might cause issues
        $orphanedInactive = ExamSession::where('is_active', false)
            ->where('completed_at', '<', now()->subDays(30)) // Older than 30 days
            ->count();
            
        if ($orphanedInactive > 0) {
            ExamSession::where('is_active', false)
                ->where('completed_at', '<', now()->subDays(30))
                ->delete();
            echo "Cleaned up {$orphanedInactive} old inactive session(s)\n";
        }
    }
};