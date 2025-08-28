<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExamSession;
use App\Models\UserScore;
use App\Models\UserAnswer;
use App\Models\User;

class CleanupOrphanedExamData extends Command
{
    protected $signature = 'exam:cleanup-orphaned';
    protected $description = 'Clean up orphaned exam sessions, scores, and answers';

    public function handle()
    {
        $this->info('Starting cleanup of orphaned exam data...');
        
        // Clean up sessions for non-existent users
        $orphanedSessions = ExamSession::whereNotIn('user_id', User::pluck('id'))->count();
        if ($orphanedSessions > 0) {
            ExamSession::whereNotIn('user_id', User::pluck('id'))->delete();
            $this->info("Deleted {$orphanedSessions} orphaned exam sessions");
        }
        
        // Clean up scores for non-existent users
        $orphanedScores = UserScore::whereNotIn('user_id', User::pluck('id'))->count();
        if ($orphanedScores > 0) {
            UserScore::whereNotIn('user_id', User::pluck('id'))->delete();
            $this->info("Deleted {$orphanedScores} orphaned user scores");
        }
        
        // Clean up answers for non-existent users
        $orphanedAnswers = UserAnswer::whereNotIn('user_id', User::pluck('id'))->count();
        if ($orphanedAnswers > 0) {
            UserAnswer::whereNotIn('user_id', User::pluck('id'))->delete();
            $this->info("Deleted {$orphanedAnswers} orphaned user answers");
        }
        
        // Clean up expired active sessions
        $expiredSessions = ExamSession::where('is_active', true)
            ->where('expires_at', '<', now())
            ->get();
        if ($expiredSessions->count() > 0) {
            foreach ($expiredSessions as $session) {
                $session->markAsCompleted(true);
            }
            $this->info("Marked {$expiredSessions->count()} expired sessions as inactive");
        }
        
        $this->info('Cleanup completed successfully!');
        return 0;
    }
}