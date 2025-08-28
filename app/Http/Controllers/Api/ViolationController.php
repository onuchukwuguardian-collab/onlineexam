<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\ViolationDetectionService;
use App\Models\ExamSession;
use App\Models\ExamBan;

/**
 * Enhanced API Controller for Subject-Specific Violation Detection
 * 
 * Handles real-time violation detection with immediate subject-specific banning
 */
class ViolationController extends Controller
{
    /**
     * Record a tab switch violation (immediate ban)
     */
    public function recordTabSwitch(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'exam_session_id' => 'nullable|exists:exam_sessions,id'
        ]);
        
        $user = Auth::user();
        $subjectId = $request->subject_id;
        $examSessionId = $request->exam_session_id;
        
        try {
            // Get subject info
            $subject = \App\Models\Subject::find($subjectId);
            
            // Get exam session
            $examSession = null;
            if ($examSessionId) {
                $examSession = ExamSession::find($examSessionId);
            }
            
            // Create the violation record
            $violation = \App\Models\ExamSecurityViolation::create([
                'user_id' => $user->id,
                'subject_id' => $subjectId,
                'exam_session_id' => $examSessionId,
                'violation_type' => 'tab_switch',
                'description' => 'Student switched tabs or opened new window during exam - IMMEDIATE BAN POLICY',
                'metadata' => [
                    'detection_method' => 'blur_focus_loss',
                    'browser_info' => [
                        'user_agent' => $request->userAgent(),
                        'screen_resolution' => $request->input('metadata.screen_resolution'),
                        'window_size' => $request->input('metadata.window_size')
                    ],
                    'violation_context' => [
                        'exam_time_elapsed' => $request->input('metadata.time_elapsed'),
                        'current_question' => $request->input('metadata.current_question'),
                        'questions_answered' => $request->input('metadata.questions_answered')
                    ],
                    'policy' => 'IMMEDIATE_BAN_ON_FIRST_VIOLATION'
                ]
            ]);
            
            // Create subject-specific ban immediately
            $existingBan = ExamBan::where('user_id', $user->id)
                ->where('subject_id', $subjectId)
                ->where('is_active', true)
                ->first();
            
            if (!$existingBan) {
                ExamBan::create([
                    'user_id' => $user->id,
                    'subject_id' => $subjectId,
                    'ban_reason' => 'IMMEDIATE_TAB_SWITCH_BAN',
                    'violation_details' => [
                        [
                            'type' => 'tab_switch',
                            'description' => 'Student switched tabs during exam - immediate ban policy',
                            'occurred_at' => now()->toISOString(),
                            'student_identification' => [
                                'registration_number' => $user->registration_number ?? 'N/A',
                                'email' => $user->email,
                                'name' => $user->name,
                                'user_id' => $user->id
                            ],
                            'tracking_method' => 'registration_and_email_based',
                            'violation_id' => $violation->id,
                            'policy' => 'IMMEDIATE_BAN_ON_FIRST_VIOLATION'
                        ]
                    ],
                    'total_violations' => 1,
                    'banned_at' => now(),
                    'is_active' => true,
                    'is_permanent' => true
                ]);
            }
            
            // Terminate exam session if it exists
            if ($examSession) {
                $this->terminateExamSession($examSessionId, 'TAB_SWITCH_BAN');
            }
            
            // Log the ban
            Log::warning('IMMEDIATE TAB SWITCH BAN CREATED', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'subject_id' => $subjectId,
                'subject_name' => $subject ? $subject->name : 'Unknown',
                'violation_id' => $violation->id,
                'banned_at' => now()
            ]);
            
            return response()->json([
                'success' => true,
                'violation_recorded' => true,
                'banned' => true,
                'permanently_banned' => true,
                'should_lock' => true,
                'violation_type' => 'tab_switch',
                'subject_name' => $subject ? $subject->name : 'this subject',
                'subject_id' => $subjectId,
                'ban_reason' => 'IMMEDIATE_TAB_SWITCH_BAN',
                'action_required' => 'IMMEDIATE_LOGOUT',
                'message' => '🚫 IMMEDIATE BAN: Tab switching detected. You are permanently banned from this subject.',
                'critical_warning_url' => '/security/critical-warning',
                'redirect_url' => '/security/critical-warning',
                'redirect_to_critical_warning' => true,
                'can_request_reactivation' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Tab switch violation recording failed: ' . $e->getMessage());
            
            // Even if recording fails, still return ban response for security
            return response()->json([
                'success' => false,
                'violation_recorded' => false,
                'banned' => true,
                'violation_type' => 'tab_switch',
                'subject_name' => 'this subject',
                'message' => 'Tab switching detected but recording failed. You are banned for security.',
                'critical_warning_url' => '/security/critical-warning',
                'redirect_to_critical_warning' => true
            ]);
        }
    }
    
    /**
     * Record a right-click violation (15 strikes system)
     */
    public function recordRightClick(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'exam_session_id' => 'nullable|exists:exam_sessions,id'
        ]);
        
        $user = Auth::user();
        $subjectId = $request->subject_id;
        $examSessionId = $request->exam_session_id;
        
        // Process violation with strike count check
        $result = ViolationDetectionService::handleRightClick(
            $user->id,
            $subjectId,
            $examSessionId,
            [
                'detection_method' => 'contextmenu_event',
                'element_target' => $request->input('target_element'),
                'page_coordinates' => [
                    'x' => $request->input('click_x'),
                    'y' => $request->input('click_y')
                ],
                'browser_info' => [
                    'user_agent' => $request->userAgent()
                ]
            ]
        );
        
        $currentCount = $result['violation_count'];
        $threshold = $result['threshold'];
        $remaining = $threshold - $currentCount;
        
        // Right-click violations never result in bans - only warnings
        // This ensures students get feedback but are never banned for right-clicking
        return response()->json([
            'success' => true,
            'violation_recorded' => true,
            'banned' => false,
            'violation_count' => $currentCount,
            'warning_level' => 'INFO',
            'message' => "⚠️ Right-click warning #{$currentCount}. Please do not right-click during exams."
        ]);
    }
    
    /**
     * Record copy/paste violation (immediate ban)
     */
    public function recordCopyPaste(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'exam_session_id' => 'nullable|exists:exam_sessions,id',
            'violation_type' => 'required|in:copy,paste,cut'
        ]);
        
        $user = Auth::user();
        $subjectId = $request->subject_id;
        $examSessionId = $request->exam_session_id;
        
        // Process violation with immediate ban
        $result = ViolationDetectionService::handleCopyPaste(
            $user->id,
            $subjectId,
            $examSessionId,
            [
                'detection_method' => 'keyboard_shortcut',
                'violation_subtype' => $request->violation_type,
                'attempted_content' => $request->input('attempted_content'),
                'source_element' => $request->input('source_element'),
                'browser_info' => [
                    'user_agent' => $request->userAgent()
                ]
            ]
        );
        
        // Copy/paste always results in immediate ban
        if ($result['ban_created']) {
            $this->terminateExamSession($examSessionId, 'COPY_PASTE_BAN');
            
            return response()->json([
                'success' => true,
                'violation_recorded' => true,
                'banned' => true,
                'ban_reason' => $result['ban_reason'],
                'action_required' => 'IMMEDIATE_LOGOUT',
                'message' => '🚫 COPY/PASTE BAN: Unauthorized copy-paste detected. You are permanently banned from this subject.',
                'redirect_url' => '/security/critical-warning',
                'can_request_reactivation' => true
            ]);
        }
        
        return response()->json([
            'success' => true,
            'violation_recorded' => true,
            'banned' => false,
            'message' => 'Copy/paste violation recorded'
        ]);
    }
    
    /**
     * Check if user can access exam for specific subject
     */
    public function checkExamAccess(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id'
        ]);
        
        $user = Auth::user();
        $subjectId = $request->subject_id;
        
        $accessInfo = ViolationDetectionService::canAccessExam($user->id, $subjectId);
        
        return response()->json([
            'success' => true,
            'access_info' => $accessInfo
        ]);
    }
    
    /**
     * Get violation status for specific subject
     */
    public function getViolationStatus(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id'
        ]);
        
        $user = Auth::user();
        $subjectId = $request->subject_id;
        
        $violationStatus = ViolationDetectionService::getViolationStatus($user->id, $subjectId);
        
        return response()->json([
            'success' => true,
            'violation_status' => $violationStatus
        ]);
    }
    
    /**
     * Check if user can request reactivation
     */
    public function checkReactivationEligibility(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id'
        ]);
        
        $user = Auth::user();
        $subjectId = $request->subject_id;
        
        // Check if banned
        $ban = ExamBan::isBannedFromSubject($user->id, $subjectId);
        
        if (!$ban) {
            return response()->json([
                'success' => true,
                'is_banned' => false,
                'can_request_reactivation' => false,
                'message' => 'You are not banned from this subject.'
            ]);
        }
        
        // Check if can request reactivation
        $canRequest = \App\Models\ReactivationRequest::canRequestReactivation($user->id, $subjectId);
        
        return response()->json([
            'success' => true,
            'is_banned' => true,
            'can_request_reactivation' => $canRequest,
            'ban_details' => [
                'ban_id' => $ban->id,
                'ban_reason' => $ban->ban_reason,
                'banned_at' => $ban->banned_at->format('Y-m-d H:i:s'),
                'violation_type' => $ban->violation_type,
                'ban_count' => $ban->ban_count
            ],
            'message' => $canRequest ? 'You can request reactivation for this subject.' : 'You already have a pending reactivation request.'
        ]);
    }
    
    /**
     * Terminate exam session due to ban
     */
    private function terminateExamSession($examSessionId, $reason)
    {
        if (!$examSessionId) {
            return;
        }
        
        $examSession = ExamSession::find($examSessionId);
        
        if ($examSession && $examSession->is_active) {
            $examSession->update([
                'is_active' => false,
                'completed_at' => now(),
                'termination_reason' => $reason,
                'last_activity_at' => now()
            ]);
            
            Log::info("Exam session {$examSessionId} terminated due to {$reason}");
        }
    }
    
    /**
     * Record generic violation with custom handling
     */
    public function recordCustomViolation(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'violation_type' => 'required|string|max:50',
            'description' => 'required|string|max:500',
            'exam_session_id' => 'nullable|exists:exam_sessions,id'
        ]);
        
        $user = Auth::user();
        $subjectId = $request->subject_id;
        $examSessionId = $request->exam_session_id;
        
        $result = ViolationDetectionService::processViolation(
            $user->id,
            $subjectId,
            $request->violation_type,
            $request->description,
            $examSessionId,
            $request->input('metadata', [])
        );
        
        if ($result['ban_created']) {
            $this->terminateExamSession($examSessionId, 'CUSTOM_VIOLATION_BAN');
        }
        
        return response()->json([
            'success' => true,
            'violation_recorded' => $result['violation_recorded'],
            'banned' => $result['ban_created'],
            'violation_count' => $result['violation_count'],
            'threshold' => $result['threshold'],
            'message' => $result['ban_created'] ? 'Violation recorded and ban triggered' : 'Violation recorded'
        ]);
    }
}