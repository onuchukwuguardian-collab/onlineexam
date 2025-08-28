<?php

namespace App\Services;

use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use App\Models\ExamSession;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ViolationDetectionService
{
    // Violation thresholds per subject
    const VIOLATION_THRESHOLDS = [
        'tab_switch' => 1,      // 1 violation = immediate ban
        'copy_attempt' => 1,    // 1 violation = immediate ban
        'right_click' => 999,   // WARNING ONLY - Never ban for right-clicks
        'dev_tools' => 1,       // 1 violation = immediate ban
        'window_blur' => 1,     // 1 violation = immediate ban
    ];

    /**
     * Process a security violation and redirect to dashboard if banned
     */
    public function processViolation($userId, $subjectId, $examSessionId, $violationType, $description, $metadata = [])
    {
        try {
            DB::beginTransaction();

            // Get user info for tracking by registration number/email
            $user = User::find($userId);
            $subject = Subject::find($subjectId);
            
            if (!$user || !$subject) {
                throw new \Exception('User or Subject not found');
            }

            // Record the violation with proper user tracking
            $violation = ExamSecurityViolation::recordViolation(
                $userId,
                $subjectId,
                $violationType,
                $description,
                $examSessionId,
                array_merge($metadata, [
                    'user_registration' => $user->registration_number ?? $user->unique_id,
                    'user_email' => $user->email,
                    'subject_name' => $subject->name
                ])
            );

            // Get violation count for this specific subject
            $violationCount = ExamSecurityViolation::getViolationCount($userId, $subjectId, $violationType);

            // Check if threshold is reached for this subject
            $threshold = self::VIOLATION_THRESHOLDS[$violationType] ?? 3;
            $shouldBan = $violationCount >= $threshold;

            $response = [
                'violation_recorded' => true,
                'violation_count' => $violationCount,
                'threshold' => $threshold,
                'should_ban' => $shouldBan,
                'subject_specific' => true,
                'message' => $this->getViolationMessage($violationType, $violationCount, $threshold, $shouldBan)
            ];

            if ($shouldBan) {
                // Create subject-specific ban
                $this->createSubjectBan($userId, $subjectId, $violationType, $violationCount);
                
                // Auto-submit exam if active
                $this->autoSubmitActiveExam($userId, $subjectId, $examSessionId);
                
                $response['banned'] = true;
                $response['redirect_to_dashboard'] = true;
                $response['dashboard_url'] = route('user.dashboard');
                $response['ban_message'] = "SECURITY VIOLATION DETECTED! You have been banned from {$subject->name}. Redirecting to dashboard where you can request reactivation.";
                
                // Log critical ban event with user tracking
                Log::critical("STUDENT BANNED - IMMEDIATE DASHBOARD REDIRECT", [
                    'user_id' => $userId,
                    'user_registration' => $user->registration_number ?? $user->unique_id,
                    'user_email' => $user->email,
                    'user_name' => $user->name,
                    'subject_id' => $subjectId,
                    'subject_name' => $subject->name,
                    'violation_type' => $violationType,
                    'violation_count' => $violationCount,
                    'threshold' => $threshold,
                    'banned_at' => now(),
                    'redirect_url' => route('user.dashboard')
                ]);
            }

            DB::commit();

            // Log the violation processing
            Log::info("Violation processed", [
                'user_id' => $userId,
                'subject_id' => $subjectId,
                'violation_type' => $violationType,
                'count' => $violationCount,
                'threshold' => $threshold,
                'banned' => $shouldBan
            ]);

            return $response;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process violation: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create subject-specific ban with enhanced offender tracking
     * ENHANCED: Comprehensive repeat offender detection and subject isolation
     */
    private function createSubjectBan($userId, $subjectId, $violationType, $violationCount)
    {
        $user = User::find($userId);
        $subject = Subject::find($subjectId);
        
        // Get all violations for this user and subject
        $violations = ExamSecurityViolation::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->orderBy('occurred_at', 'desc')
            ->get();

        // Check for previous bans in this subject (repeat offender detection)
        $previousBansInSubject = ExamBan::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->count();
            
        // Check for bans in other subjects (cross-subject offender pattern)
        $bansInOtherSubjects = ExamBan::where('user_id', $userId)
            ->where('subject_id', '!=', $subjectId)
            ->count();

        // Create the ban using the enhanced method
        $ban = ExamBan::createSubjectBan(
            $userId,
            $subjectId,
            $violationType,
            $violations,
            "SUBJECT-SPECIFIC BAN: {$violationCount} {$violationType} violations in {$subject->name}"
        );

        // Enhanced logging with comprehensive offender tracking
        Log::critical("ENHANCED SUBJECT-SPECIFIC BAN CREATED", [
            'ban_id' => $ban->id,
            'user_tracking' => [
                'user_id' => $userId,
                'registration_number' => $user->registration_number ?? 'N/A',
                'email' => $user->email,
                'name' => $user->name,
                'tracking_method' => 'registration_and_email_based'
            ],
            'subject_tracking' => [
                'subject_id' => $subjectId,
                'subject_name' => $subject->name,
                'subject_isolation' => true,
                'other_subjects_accessible' => true
            ],
            'violation_details' => [
                'violation_type' => $violationType,
                'violation_count' => $violationCount,
                'total_violations_in_subject' => $violations->count()
            ],
            'offender_analysis' => [
                'is_repeat_offender_in_subject' => $previousBansInSubject > 0,
                'previous_bans_in_this_subject' => $previousBansInSubject,
                'bans_in_other_subjects' => $bansInOtherSubjects,
                'total_ban_count' => $previousBansInSubject + $bansInOtherSubjects + 1,
                'offender_pattern' => $bansInOtherSubjects > 0 ? 'cross_subject_offender' : 'single_subject_offender'
            ],
            'ban_enforcement' => [
                'ban_scope' => 'subject_specific_only',
                'affected_subject' => $subject->name,
                'unaffected_subjects' => 'all_other_subjects',
                'reactivation_required' => 'admin_approval_only'
            ],
            'timestamp' => now()->toISOString()
        ]);

        return $ban;
    }

    /**
     * Auto-submit active exam session
     */
    private function autoSubmitActiveExam($userId, $subjectId, $examSessionId)
    {
        $examSession = ExamSession::where('id', $examSessionId)
            ->where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->first();

        if ($examSession) {
            // Mark as completed due to violation
            $examSession->markAsCompleted(true);
            
            Log::info("Exam auto-submitted due to ban", [
                'exam_session_id' => $examSessionId,
                'user_id' => $userId,
                'subject_id' => $subjectId
            ]);
        }
    }

    /**
     * Get violation message based on type and count
     */
    private function getViolationMessage($violationType, $count, $threshold, $shouldBan)
    {
        $subjectSpecific = " (This ban only affects this subject - you can still take other exams)";

        if ($shouldBan) {
            switch ($violationType) {
                case 'tab_switch':
                    return "🚫 BANNED: Tab switching detected! You are now banned from this subject." . $subjectSpecific;
                case 'copy_attempt':
                    return "🚫 BANNED: Copy/paste attempt detected! You are now banned from this subject." . $subjectSpecific;
                case 'dev_tools':
                    return "🚫 BANNED: Developer tools access detected! You are now banned from this subject." . $subjectSpecific;
                case 'window_blur':
                    return "🚫 BANNED: Window switching detected! You are now banned from this subject." . $subjectSpecific;
                default:
                    return "🚫 BANNED: Security violation limit reached ({$count}/{$threshold})! You are now banned from this subject." . $subjectSpecific;
            }
        } else {
            $remaining = $threshold - $count;
            switch ($violationType) {
                case 'right_click':
                    return "⚠️ WARNING: Right-click attempt #{$count} detected! Please do not right-click during exams.";
                default:
                    return "⚠️ WARNING: {$violationType} violation #{$count} detected! {$remaining} more violations will result in a ban from this subject.";
            }
        }
    }

    /**
     * Check if user is banned from specific subject
     * ENHANCED: Comprehensive subject-specific ban checking with detailed logging
     */
    public function isUserBannedFromSubject($userId, $subjectId)
    {
        $user = User::find($userId);
        $subject = Subject::find($subjectId);
        
        $banRecord = ExamBan::isBannedFromSubject($userId, $subjectId);
        
        if ($banRecord) {
            Log::info("SUBJECT-SPECIFIC BAN CHECK: Student is banned", [
                'user_id' => $userId,
                'user_registration' => $user->registration_number ?? 'N/A',
                'user_email' => $user->email ?? 'N/A',
                'user_name' => $user->name ?? 'N/A',
                'subject_id' => $subjectId,
                'subject_name' => $subject->name ?? 'Unknown',
                'ban_id' => $banRecord->id,
                'ban_reason' => $banRecord->ban_reason,
                'banned_at' => $banRecord->banned_at,
                'total_violations' => $banRecord->total_violations,
                'ban_count' => $banRecord->ban_count ?? 1,
                'is_repeat_offender' => ($banRecord->ban_count ?? 1) > 1,
                'subject_isolation_confirmed' => true,
                'other_subjects_accessible' => true
            ]);
        }
        
        return $banRecord;
    }

    /**
     * Get user's violation count for specific subject and type
     */
    public function getViolationCount($userId, $subjectId, $violationType)
    {
        return ExamSecurityViolation::getViolationCount($userId, $subjectId, $violationType);
    }

    /**
     * Get all violations for user and subject
     */
    public function getUserSubjectViolations($userId, $subjectId)
    {
        return ExamSecurityViolation::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->with(['user', 'subject'])
            ->orderBy('occurred_at', 'desc')
            ->get();
    }

    /**
     * Get subject name
     */
    private function getSubjectName($subjectId)
    {
        $subject = Subject::find($subjectId);
        return $subject ? $subject->name : "Subject #{$subjectId}";
    }

    /**
     * Get violation statistics for admin dashboard
     */
    public function getViolationStatistics($subjectId = null, $days = 30)
    {
        $query = ExamSecurityViolation::query();

        if ($subjectId) {
            $query->where('subject_id', $subjectId);
        }

        if ($days) {
            $query->where('occurred_at', '>=', now()->subDays($days));
        }

        $violations = $query->get();

        return [
            'total_violations' => $violations->count(),
            'by_type' => $violations->groupBy('violation_type')->map->count(),
            'by_subject' => $violations->groupBy('subject_id')->map->count(),
            'unique_users' => $violations->pluck('user_id')->unique()->count(),
            'bans_created' => ExamBan::where('created_at', '>=', now()->subDays($days))->count()
        ];
    }

    /**
     * Reset violations for a user and subject (used after reactivation)
     */
    public function resetUserSubjectViolations($userId, $subjectId)
    {
        try {
            DB::beginTransaction();

            // Delete all violations for this user and subject
            ExamSecurityViolation::where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->delete();

            // Remove any bans
            ExamBan::where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->delete();

            DB::commit();

            Log::info("Violations reset for user and subject", [
                'user_id' => $userId,
                'subject_id' => $subjectId
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset violations: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is banned from a specific subject
     */
    public static function checkBanStatus($userId, $subjectId)
    {
        return ExamBan::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Reset violations for a subject after reactivation
     */
    public static function resetViolationsForSubject($userId, $subjectId, $adminId = null, $reason = null)
    {
        try {
            DB::beginTransaction();

            // Delete all violations for this user and subject
            $deletedViolations = ExamSecurityViolation::where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->delete();

            // Deactivate any active bans
            ExamBan::where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'reactivated_at' => now(),
                    'reactivated_by' => $adminId,
                    'reactivation_reason' => $reason ?? 'Violations reset by admin'
                ]);

            DB::commit();

            Log::info("Violations reset for subject reactivation", [
                'user_id' => $userId,
                'subject_id' => $subjectId,
                'admin_id' => $adminId,
                'deleted_violations' => $deletedViolations,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reset violations for subject: ' . $e->getMessage());
            return false;
        }
    }
}