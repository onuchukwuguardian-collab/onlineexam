<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'started_at',
        'expires_at',
        'duration_minutes',
        'answers',
        'current_question_index',
        'is_active',
        'auto_submitted',
        'last_activity_at',
        'completed_at',
        'has_security_violation',
        'violation_occurred_at',
        'violation_reason'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'completed_at' => 'datetime',
        'violation_occurred_at' => 'datetime',
        'answers' => 'array',
        'is_active' => 'boolean',
        'auto_submitted' => 'boolean',
        'has_security_violation' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function getRemainingTimeAttribute()
    {
        if (!$this->is_active) {
            return 0;
        }

        // TIMER NEVER PAUSES - Always calculate based on actual elapsed time since start
        // No mercy, no pausing, timer runs continuously from start to finish
        $now = Carbon::now();
        $sessionStart = Carbon::parse($this->started_at);
        $elapsedSeconds = $now->diffInSeconds($sessionStart);
        $totalDurationSeconds = $this->duration_minutes * 60;
        
        $remainingSeconds = $totalDurationSeconds - $elapsedSeconds;
        
        return max(0, $remainingSeconds);
    }

    public function isExpired()
    {
        // TIMER NEVER PAUSES - Check if session has expired based on duration and start time ONLY
        // No mercy for inactivity, logout, or navigation - timer keeps running regardless
        $now = Carbon::now();
        $sessionStart = Carbon::parse($this->started_at);
        $elapsedMinutes = $now->diffInMinutes($sessionStart);
        
        return $elapsedMinutes >= $this->duration_minutes;
    }
    
    public function isExpiredWithGrace($graceMinutes = 5)
    {
        $now = Carbon::now();
        $sessionStart = Carbon::parse($this->started_at);
        $elapsedMinutes = $now->diffInMinutes($sessionStart);
        
        return $elapsedMinutes >= ($this->duration_minutes + $graceMinutes);
    }

    public function updateActivity()
    {
        $this->update(['last_activity_at' => Carbon::now()]);
    }
    
    public function getActualRemainingTimeAttribute()
    {
        // Use the same calculation as getRemainingTimeAttribute for consistency
        return $this->getRemainingTimeAttribute();
    }

    public function saveProgress($answers, $currentQuestionIndex)
    {
        $this->update([
            'answers' => $answers,
            'current_question_index' => $currentQuestionIndex,
            'last_activity_at' => Carbon::now()
        ]);
    }

    public function markAsCompleted($autoSubmitted = false)
    {
        // First, delete any existing inactive sessions for this user/subject to avoid constraint violations
        self::where('user_id', $this->user_id)
            ->where('subject_id', $this->subject_id)
            ->where('is_active', false)
            ->delete();
            
        $this->update([
            'is_active' => false,
            'auto_submitted' => $autoSubmitted,
            'last_activity_at' => now(),
            'completed_at' => now(),
        ]);
    }
    
    /**
     * Clean up session when user navigates away or logs out
     */
    public function updateLastActivity()
    {
        $this->update([
            'last_activity_at' => now()
        ]);
    }
    
    /**
     * Check if session should be considered abandoned
     * NO MERCY - Timer never stops, no matter what
     */
    public function isAbandoned()
    {
        // Sessions are never considered "abandoned" - timer always runs
        return false;
    }
    
    /**
     * Safely create a new exam session, cleaning up any conflicting records
     */
    public static function createSafely(array $attributes)
    {
        $userId = $attributes['user_id'];
        $subjectId = $attributes['subject_id'];
        
        // Clean up any existing inactive sessions for this user/subject to avoid constraint violations
        self::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('is_active', false)
            ->delete();
            
        // Also handle any existing active sessions by marking them as completed
        // Use direct update to avoid race conditions
        self::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->update(['is_active' => false, 'completed_at' => now(), 'updated_at' => now()]);
            
        // Add a small delay to ensure database consistency
        usleep(100000); // 100ms delay
        
        // Ensure fresh timestamps for new session
        $now = \Carbon\Carbon::now();
        $attributes['started_at'] = $now;
        $attributes['expires_at'] = $now->copy()->addMinutes($attributes['duration_minutes']);
        $attributes['last_activity_at'] = $now;
        
        \Log::info("Creating fresh exam session for user {$userId}, subject {$subjectId}");
        
        try {
            return self::create($attributes);
        } catch (\Illuminate\Database\QueryException $e) {
            // If we still get a duplicate entry error, force deactivate all sessions and try again
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                \Log::warning("Duplicate entry detected, forcing deactivation for user {$userId}, subject {$subjectId}");
                self::where('user_id', $userId)
                    ->where('subject_id', $subjectId)
                    ->update(['is_active' => false, 'completed_at' => now(), 'updated_at' => now()]);
                
                // Try one more time
                return self::create($attributes);
            }
            
            throw $e; // Re-throw if it's a different error
        }
    }
    
    /**
     * Mark session as having a security violation
     * This will reset progress when student returns
     */
    public function markAsViolated($reason = 'Security violation detected')
    {
        $this->update([
            'has_security_violation' => true,
            'violation_occurred_at' => now(),
            'violation_reason' => $reason,
            'is_active' => false, // Deactivate session
            'last_activity_at' => now()
        ]);
    }
    
    /**
     * Reset session progress due to security violation
     * Student must start from question 1 again
     */
    public function resetProgressDueToViolation()
    {
        $this->update([
            'answers' => [], // Clear all answers
            'current_question_index' => 0, // Reset to first question
            'is_active' => true, // Reactivate session
            'last_activity_at' => now()
        ]);
    }
    
    /**
     * Check if session can be resumed normally
     * Returns false if there was a security violation
     */
    public function canResumeNormally()
    {
        return !$this->has_security_violation;
    }
}
