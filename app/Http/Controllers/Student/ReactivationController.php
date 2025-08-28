<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ReactivationRequest;
use App\Models\ExamBan;
use App\Models\Subject;
use App\Models\ExamSecurityViolation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

class ReactivationController extends Controller
{
    /**
     * Show reactivation request form
     * CLEAN APPROACH: Only show actual ExamBan records, ignore fake violations
     */
    public function index()
    {
        $user = Auth::user();
        
        Log::info('📋 REACTIVATION PAGE ACCESS - CLEAN APPROACH', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email
        ]);
        
        // CLEAN SOLUTION: Only get actual ExamBan records that exist in the database
        $bannedSubjects = ExamBan::with('subject')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
            
        Log::info('✅ ACTUAL BANNED SUBJECTS FROM DATABASE', [
            'user_id' => $user->id,
            'banned_subjects_count' => $bannedSubjects->count(),
            'banned_subjects' => $bannedSubjects->map(function($ban) {
                return [
                    'ban_id' => $ban->id,
                    'subject_name' => $ban->subject ? $ban->subject->name : 'Unknown',
                    'ban_reason' => substr($ban->ban_reason, 0, 50) . '...',
                    'banned_at' => $ban->banned_at->format('Y-m-d H:i:s'),
                    'is_active' => $ban->is_active
                ];
            })->toArray()
        ]);

        // Get existing reactivation requests
        $existingRequests = ReactivationRequest::where('user_id', $user->id)
            ->with(['subject'])
            ->orderBy('requested_at', 'desc')
            ->get();
            
        Log::info('📨 EXISTING REACTIVATION REQUESTS', [
            'user_id' => $user->id,
            'requests_count' => $existingRequests->count(),
            'requests' => $existingRequests->map(function($req) {
                return [
                    'id' => $req->id,
                    'subject' => $req->subject ? $req->subject->name : 'Unknown',
                    'status' => $req->status,
                    'requested_at' => $req->requested_at->format('Y-m-d H:i:s')
                ];
            })->toArray()
        ]);

        return view('student.reactivation.index', compact('bannedSubjects', 'existingRequests'));
    }

    /**
     * Show reactivation request form for specific subject
     * CLEAN APPROACH: Only work with actual ExamBan records
     */
    public function create($subjectId)
    {
        $user = Auth::user();
        $subject = Subject::findOrFail($subjectId);
        
        // Check if user has an actual active ban for this subject
        $ban = ExamBan::where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->first();
            
        if (!$ban) {
            return redirect()->route('user.student.reactivation.index')
                ->with('error', 'You are not currently banned from this subject.');
        }
        
        // Check if there's already a pending request
        $existingRequest = ReactivationRequest::where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('status', 'pending')
            ->first();
            
        if ($existingRequest) {
            return redirect()->route('user.student.reactivation.index')
                ->with('error', 'You already have a pending reactivation request for this subject.');
        }

        return view('student.reactivation.create', compact('subject', 'ban'));
    }

    /**
     * Store reactivation request
     * ENHANCED: Handle missing ban records by creating them from violations
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'request_message' => 'required|string|min:50|max:1000'
        ]);

        $user = Auth::user();
        $subjectId = $request->subject_id;
        $subject = Subject::findOrFail($subjectId);

        // Check if user has an actual active ban for this subject
        $ban = ExamBan::where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->first();
            
        // If no ban exists, check if there are violations that should have created a ban
        if (!$ban) {
            $violations = ExamSecurityViolation::where('user_id', $user->id)
                ->where('subject_id', $subjectId)
                ->orderBy('occurred_at', 'desc')
                ->get();
                
            // Only create ban for violations that actually warrant banning
            if ($violations->isNotEmpty()) {
                $tabSwitchViolations = $violations->where('violation_type', 'tab_switch');
                
                // Tab switch = immediate ban (right-click is WARNING ONLY, never a ban)
                if ($tabSwitchViolations->isNotEmpty()) {
                    $violationType = 'tab_switch';
                    $violationCount = $tabSwitchViolations->count();
                    
                    // Create the missing ban record
                    $ban = ExamBan::create([
                        'user_id' => $user->id,
                        'subject_id' => $subjectId,
                        'ban_reason' => 'IMMEDIATE_TAB_SWITCH_BAN - Retroactively created from violation records',
                        'violation_details' => $tabSwitchViolations->map(function($v) {
                            return [
                                'type' => $v->violation_type,
                                'description' => $v->description,
                                'occurred_at' => $v->occurred_at->toISOString(),
                                'violation_id' => $v->id
                            ];
                        })->toArray(),
                        'violation_type' => $violationType,
                        'total_violations' => $violationCount,
                        'banned_at' => now(),
                        'is_active' => true,
                        'is_permanent' => true,
                        'admin_notes' => 'Ban created retroactively from tab switch violation records during reactivation request'
                    ]);
                    
                    Log::info('Created missing ban record during reactivation request', [
                        'user_id' => $user->id,
                        'subject_id' => $subjectId,
                        'violation_type' => $violationType,
                        'ban_id' => $ban->id
                    ]);
                }
            }
            
            // If still no ban after checking violations, user is not actually banned
            if (!$ban) {
                return back()->withErrors(['error' => 'You are not currently banned from this subject.']);
            }
        }

        // Check if there's already a pending request
        $existingRequest = ReactivationRequest::where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('status', 'pending')
            ->first();
            
        if ($existingRequest) {
            return back()->withErrors(['error' => 'You already have a pending reactivation request for this subject.']);
        }

        // Create the reactivation request
        $result = ReactivationRequest::createRequest(
            $user->id,
            $subjectId,
            $ban->id,
            $request->request_message
        );

        if ($result['success']) {
            // IMMEDIATE ADMIN NOTIFICATION
            $this->notifyAdminsImmediately($user, $subject, $ban, $request->request_message);
            
            return redirect()->route('user.student.reactivation.index')
                ->with('success', 'Your reactivation request has been submitted successfully. Administrators have been notified immediately and will review your request.');
        } else {
            return back()->withErrors(['error' => $result['message']]);
        }
    }

    /**
     * Show specific reactivation request
     */
    public function show(ReactivationRequest $request)
    {
        $user = Auth::user();
        
        // Ensure the request belongs to the authenticated user
        if ($request->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this reactivation request.');
        }

        $request->load(['subject', 'examBan', 'reviewedByAdmin']);

        return view('student.reactivation.show', compact('request'));
    }

    /**
     * Get reactivation status via API
     */
    public function status($subjectId)
    {
        $user = Auth::user();
        
        // Check if banned
        $isBanned = ExamBan::isBanned($user->id, $subjectId);
        
        if (!$isBanned) {
            return response()->json([
                'banned' => false,
                'can_request' => false,
                'message' => 'You are not banned from this subject.'
            ]);
        }

        // Check if can request reactivation
        $canRequest = ReactivationRequest::canRequestReactivation($user->id, $subjectId);
        
        // Get existing request if any
        $existingRequest = ReactivationRequest::where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->orderBy('requested_at', 'desc')
            ->first();

        return response()->json([
            'banned' => true,
            'can_request' => $canRequest['can_request'],
            'reason' => $canRequest['reason'] ?? null,
            'existing_request' => $existingRequest ? [
                'id' => $existingRequest->id,
                'status' => $existingRequest->status,
                'requested_at' => $existingRequest->formatted_request_date,
                'admin_response' => $existingRequest->admin_response
            ] : null
        ]);
    }
    
    /**
     * Immediately notify all administrators when a student requests reactivation
     * This ensures "simple as abc" workflow as requested by user
     */
    private function notifyAdminsImmediately($user, $subject, $ban, $requestMessage)
    {
        try {
            // Get all admin users
            $admins = User::where('role', 'admin')->get();
            
            // Log the reactivation request with CRITICAL level for immediate admin attention
            Log::critical("🚨 IMMEDIATE ADMIN ACTION REQUIRED: Student Reactivation Request", [
                'urgency' => 'IMMEDIATE',
                'action_required' => 'ADMIN_REVIEW_REACTIVATION',
                'student_name' => $user->name,
                'student_email' => $user->email,
                'student_registration' => $user->registration_number,
                'banned_subject' => $subject->name,
                'ban_reason' => $ban->ban_reason,
                'student_message' => $requestMessage,
                'admin_dashboard_url' => route('admin.security.reactivation-requests'),
                'reactivation_requests_url' => '/admin/security/reactivation-requests', // Direct link for admins
                'timestamp' => now()->toISOString(),
                'admin_count' => $admins->count()
            ]);
            
            // Create a notification in application logs specifically for admins
            Log::info("📢 ADMIN NOTIFICATION: {$user->name} requests reactivation for {$subject->name}", [
                'notification_type' => 'reactivation_request',
                'priority' => 'high',
                'student' => $user->name,
                'subject' => $subject->name,
                'admin_action_url' => route('admin.security.reactivation-requests')
            ]);
            
            // Attempt to notify admins via proper Laravel notifications
            foreach ($admins as $admin) {
                try {
                    // Create notification data
                    $notificationData = [
                        'message' => "Student {$user->name} has requested reactivation for {$subject->name}",
                        'student_id' => $user->id,
                        'student_name' => $user->name,
                        'subject_id' => $subject->id,
                        'subject_name' => $subject->name,
                        'action_url' => route('admin.security.reactivation-requests'),
                        'created_at' => now()->toISOString(),
                    ];
                    
                    // Send notification using Laravel's notification system
                    $admin->notify(new \App\Notifications\ReactivationRequestNotification($notificationData));
                    
                } catch (\Exception $e) {
                    // Just log the error but continue - we don't want to fail the whole process
                    Log::warning('Failed to create database notification: ' . $e->getMessage());
                    
                    // Fallback to manual DB insertion if the notification system fails
                    try {
                        DB::table('notifications')->insert([
                            'id' => \Illuminate\Support\Str::uuid()->toString(),
                            'type' => 'App\\Notifications\\ReactivationRequestNotification',
                            'notifiable_type' => 'App\\Models\\User',
                            'notifiable_id' => $admin->id,
                            'data' => json_encode([
                                'message' => "Student {$user->name} has requested reactivation for {$subject->name}",
                                'student_id' => $user->id,
                                'student_name' => $user->name,
                                'subject_id' => $subject->id,
                                'subject_name' => $subject->name,
                                'action_url' => route('admin.security.reactivation-requests'),
                            ]),
                            'created_at' => now(),
                            'updated_at' => now(),
                            'read_at' => null,
                        ]);
                    } catch (\Exception $dbEx) {
                        Log::error('Failed to manually insert notification: ' . $dbEx->getMessage());
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to notify admins of reactivation request: ' . $e->getMessage());
        }
    }
}