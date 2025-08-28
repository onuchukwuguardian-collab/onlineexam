<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use App\Models\User;
use App\Models\Subject;
use Carbon\Carbon;

class SecurityViolationController extends Controller
{
    /**
     * Security Dashboard - Clean and Professional
     */
    public function index(Request $request)
    {
        // Get comprehensive statistics
        $stats = $this->getSecurityStatistics();
        
        // Get recent violations with pagination
        $violations = ExamSecurityViolation::with(['user:id,name,email,registration_number', 'subject:id,name'])
            ->orderBy('occurred_at', 'desc')
            ->paginate(15);
        
        // Get currently banned students
        $bannedStudents = ExamBan::with(['user:id,name,email,registration_number', 'subject:id,name'])
            ->where('is_active', true)
            ->orderBy('banned_at', 'desc')
            ->paginate(10);
        
        return view('admin.security.index', compact('stats', 'violations', 'bannedStudents'));
    }
    
    /**
     * Get security statistics
     */
    private function getSecurityStatistics()
    {
        return [
            'total_violations' => ExamSecurityViolation::count(),
            'violations_today' => ExamSecurityViolation::whereDate('occurred_at', today())->count(),
            'violations_week' => ExamSecurityViolation::where('occurred_at', '>=', now()->subWeek())->count(),
            'active_bans' => ExamBan::where('is_active', true)->count(),
            'tab_switches' => ExamSecurityViolation::where('violation_type', 'tab_switch')->count(),
            'right_clicks' => ExamSecurityViolation::where('violation_type', 'right_click')->count(),
            'copy_attempts' => ExamSecurityViolation::where('violation_type', 'copy_attempt')->count(),
            'unique_violators' => ExamSecurityViolation::distinct('user_id')->count(),
        ];
    }
    
    /**
     * Show violation details
     */
    public function showViolation(ExamSecurityViolation $violation)
    {
        $violation->load(['user', 'subject']);
        
        // Get user's violation history
        $userViolations = ExamSecurityViolation::with('subject')
            ->where('user_id', $violation->user_id)
            ->orderBy('occurred_at', 'desc')
            ->get();
        
        // Check if user is banned
        $banStatus = ExamBan::where('user_id', $violation->user_id)
            ->where('subject_id', $violation->subject_id)
            ->where('is_active', true)
            ->first();
        
        return view('admin.security.violation-details', compact('violation', 'userViolations', 'banStatus'));
    }
    
    /**
     * Ban a student (Admin Action) - ENHANCED with proper banned table logic
     */
    public function banStudent(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'reason' => 'required|string|max:500',
            'violation_type' => 'string|in:tab_switch,right_click,copy_paste,browser_navigation'
        ]);
        
        $admin = Auth::user();
        
        // Check if student is already banned for this subject
        $existingBan = ExamBan::where('user_id', $request->user_id)
            ->where('subject_id', $request->subject_id)
            ->where('is_active', true)
            ->first();
        
        if ($existingBan) {
            return response()->json([
                'success' => false,
                'message' => 'Student is already banned for this subject.'
            ], 400);
        }
        
        DB::beginTransaction();
        try {
            // Get user and subject details
            $user = User::find($request->user_id);
            $subject = Subject::find($request->subject_id);
            
            // Get all violations for this user-subject combination
            $violations = ExamSecurityViolation::where('user_id', $request->user_id)
                ->where('subject_id', $request->subject_id)
                ->get();
            
            // Get violation type from violations or request
            $violationType = $request->violation_type ?? (
                $violations->isNotEmpty() ? $violations->first()->violation_type : 'manual_ban'
            );
            
            // Create subject-specific ban using enhanced model method
            $ban = ExamBan::createSubjectBan(
                $request->user_id,
                $request->subject_id,
                $violationType,
                $violations,
                $request->reason
            );
            
            // Update ban with admin information
            $ban->update([
                'banned_by_admin_id' => $admin->id,
                'admin_notes' => "ADMIN MANUAL BAN: {$request->reason} | Banned by: {$admin->name} (ID: {$admin->id}) | Student: {$user->name} (Reg: {$user->registration_number}) | Subject: {$subject->name}"
            ]);
            
            // Log comprehensive admin action
            Log::critical('ADMIN MANUALLY BANNED STUDENT', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'student_id' => $request->user_id,
                'student_name' => $user->name,
                'student_registration' => $user->registration_number,
                'subject_id' => $request->subject_id,
                'subject_name' => $subject->name,
                'ban_reason' => $request->reason,
                'violation_type' => $violationType,
                'total_violations' => $violations->count(),
                'ban_id' => $ban->id,
                'ban_scope' => 'subject_specific_only',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Student {$user->name} has been banned from {$subject->name}. This ban is subject-specific only.",
                'ban_details' => [
                    'student' => $user->name,
                    'subject' => $subject->name,
                    'ban_count' => $ban->ban_count,
                    'scope' => 'Subject-specific only',
                    'other_subjects' => 'Remain accessible'
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to ban student: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'student_id' => $request->user_id,
                'subject_id' => $request->subject_id,
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to ban student. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Unban a student (Admin Action) - ENHANCED with proper reactivation logic
     */
    public function unbanStudent(Request $request, ExamBan $ban)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);
        
        $admin = Auth::user();
        
        // Check if ban is already inactive
        if (!$ban->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'This ban is already inactive.'
            ], 400);
        }
        
        DB::beginTransaction();
        try {
            // Load related models for logging
            $ban->load(['user', 'subject']);
            
            // Get current ban statistics for this user
            $userStats = ExamBan::getOffenderStatistics($ban->user_id);
            
            // Update ban record with comprehensive reactivation data
            $ban->update([
                'is_active' => false,
                'reactivated_at' => now(),
                'reactivated_by' => $admin->id,
                'reactivation_reason' => $request->reason,
                'admin_notes' => $ban->admin_notes . " | REACTIVATED by {$admin->name} (ID: {$admin->id}) on " . now()->format('Y-m-d H:i:s') . " | Reason: {$request->reason}"
            ]);
            
            // Log comprehensive reactivation action
            Log::critical('ADMIN REACTIVATED BANNED STUDENT', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'student_id' => $ban->user_id,
                'student_name' => $ban->user->name,
                'student_registration' => $ban->user->registration_number,
                'subject_id' => $ban->subject_id,
                'subject_name' => $ban->subject->name,
                'ban_id' => $ban->id,
                'original_ban_reason' => $ban->ban_reason,
                'reactivation_reason' => $request->reason,
                'ban_duration' => $ban->banned_at->diffForHumans($ban->reactivated_at, true),
                'user_statistics' => [
                    'total_bans_across_subjects' => $userStats['overall_statistics']['total_bans_across_all_subjects'] ?? 0,
                    'currently_banned_subjects' => $userStats['overall_statistics']['currently_banned_subjects'] ?? 0,
                    'offender_classification' => $userStats['overall_statistics']['offender_classification'] ?? 'unknown'
                ],
                'reactivation_scope' => 'subject_specific_only',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString()
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Student {$ban->user->name} has been reactivated for {$ban->subject->name}.",
                'reactivation_details' => [
                    'student' => $ban->user->name,
                    'subject' => $ban->subject->name,
                    'ban_duration' => $ban->banned_at->diffForHumans($ban->reactivated_at, true),
                    'scope' => 'Subject-specific reactivation',
                    'access_restored' => 'Full access to ' . $ban->subject->name
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to unban student: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'ban_id' => $ban->id,
                'student_id' => $ban->user_id,
                'subject_id' => $ban->subject_id,
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to unban student. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Export violations report
     */
    public function exportReport(Request $request)
    {
        $format = $request->get('format', 'csv');
        $dateFrom = $request->get('date_from', now()->subMonth()->format('Y-m-d'));
        $dateTo = $request->get('date_to', now()->format('Y-m-d'));
        
        $violations = ExamSecurityViolation::with(['user', 'subject'])
            ->whereBetween('occurred_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->orderBy('occurred_at', 'desc')
            ->get();
        
        if ($format === 'csv') {
            return $this->exportToCsv($violations, $dateFrom, $dateTo);
        }
        
        return redirect()->back()->with('error', 'Invalid export format.');
    }
    
    /**
     * Export to CSV
     */
    private function exportToCsv($violations, $dateFrom, $dateTo)
    {
        $filename = "security_violations_{$dateFrom}_to_{$dateTo}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];
        
        $callback = function() use ($violations) {
            $file = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($file, [
                'Date/Time', 'Student Name', 'Student Email', 'Registration',
                'Subject', 'Violation Type', 'Description', 'IP Address'
            ]);
            
            foreach ($violations as $violation) {
                fputcsv($file, [
                    $violation->occurred_at->format('Y-m-d H:i:s'),
                    $violation->user->name ?? 'Unknown',
                    $violation->user->email ?? 'Unknown',
                    $violation->user->registration_number ?? 'N/A',
                    $violation->subject->name ?? 'Unknown',
                    $violation->violation_type,
                    $violation->description,
                    $violation->ip_address
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Get security statistics API
     */
    public function getStats()
    {
        return response()->json($this->getSecurityStatistics());
    }
    
    /**
     * Clear old violations (Admin utility)
     */
    public function clearOldViolations(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:30|max:365'
        ]);
        
        $admin = Auth::user();
        $cutoffDate = now()->subDays($request->days);
        
        DB::beginTransaction();
        try {
            $deletedCount = ExamSecurityViolation::where('occurred_at', '<', $cutoffDate)->delete();
            
            Log::info('Admin cleared old violations', [
                'admin_id' => $admin->id,
                'cutoff_date' => $cutoffDate,
                'deleted_count' => $deletedCount
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "Cleared {$deletedCount} old violations."
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to clear old violations: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear violations. Please try again.'
            ], 500);
        }
    }

    /**
     * Show critical warning page for banned students
     * @deprecated Use the SecurityViewController version instead
     */
    public function showCriticalWarning()
    {
        // Redirect to new critical warning page
        return redirect()->route('security.critical-warning');
    }
    
    /**
     * Reactivation Requests Dashboard for administrators
     */
    public function reactivationRequests(Request $request)
    {
        $admin = Auth::user();
        
        // Ensure user is admin
        if (!$admin->role === 'admin') {
            abort(403, 'Unauthorized access');
        }
        
        $status = $request->get('status', 'all');
        $subject = $request->get('subject');
        $search = $request->get('search');
        
        // Get reactivation requests with filters
        $query = \App\Models\ReactivationRequest::with(['user', 'subject', 'examBan', 'reviewer'])
            ->when($status !== 'all', function($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($subject, function($q) use ($subject) {
                return $q->where('subject_id', $subject);
            })
            ->when($search, function($q) use ($search) {
                return $q->whereHas('user', function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->latest('requested_at');
        
        $requests = $query->paginate(15);
        $subjects = Subject::orderBy('name')->get();
        
        // Get statistics for dashboard
        $stats = [
            'total_requests' => \App\Models\ReactivationRequest::count(),
            'pending_requests' => \App\Models\ReactivationRequest::where('status', 'pending')->count(),
            'approved_requests' => \App\Models\ReactivationRequest::where('status', 'approved')->count(),
            'rejected_requests' => \App\Models\ReactivationRequest::where('status', 'rejected')->count(),
            'approved_today' => \App\Models\ReactivationRequest::where('status', 'approved')
                ->whereDate('reviewed_at', now()->toDateString())->count(),
            'rejected_today' => \App\Models\ReactivationRequest::where('status', 'rejected')
                ->whereDate('reviewed_at', now()->toDateString())->count(),
            'avg_response_time' => \App\Models\ReactivationRequest::whereNotNull('reviewed_at')
                ->whereNotNull('requested_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, requested_at, reviewed_at)) as avg_hours')
                ->first()?->avg_hours ?? 0
        ];
        
        return view('admin.security.reactivation-requests', compact('requests', 'subjects', 'stats'));
    }
    
    /**
     * Show individual reactivation request details
     */
    public function showReactivationRequest(\App\Models\ReactivationRequest $reactivationRequest)
    {
        $admin = Auth::user();
        
        // Ensure user is admin
        if (!$admin->role === 'admin') {
            abort(403, 'Unauthorized access');
        }
        
        $reactivationRequest->load(['user', 'subject', 'examBan', 'reviewer']);
        
        // Get user's violation history
        $violations = ExamSecurityViolation::where('user_id', $reactivationRequest->user_id)
            ->where('subject_id', $reactivationRequest->subject_id)
            ->orderBy('occurred_at', 'desc')
            ->get();
        
        return view('admin.security.reactivation-request-details', 
            compact('reactivationRequest', 'violations'));
    }
    
    /**
     * Approve a reactivation request
     */
    public function approveReactivationRequest(Request $request, \App\Models\ReactivationRequest $reactivationRequest)
    {
        $admin = Auth::user();
        
        // Ensure user is admin
        if (!$admin->role === 'admin') {
            abort(403, 'Unauthorized access');
        }
        
        $result = $reactivationRequest->approve(
            $admin->id, 
            $request->input('admin_response')
        );
        
        if ($result['success']) {
            // Log critical admin action
            Log::critical("ADMIN REACTIVATED STUDENT: {$reactivationRequest->user->name} for {$reactivationRequest->subject->name}", [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'student_id' => $reactivationRequest->user_id,
                'student_name' => $reactivationRequest->user->name,
                'subject_id' => $reactivationRequest->subject_id,
                'subject_name' => $reactivationRequest->subject->name,
                'request_id' => $reactivationRequest->id,
                'admin_response' => $request->input('admin_response'),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Student {$reactivationRequest->user->name} has been reactivated for {$reactivationRequest->subject->name}."
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to approve reactivation request.'
        ], 500);
    }
    
    /**
     * Reject a reactivation request
     */
    public function rejectReactivationRequest(Request $request, \App\Models\ReactivationRequest $reactivationRequest)
    {
        $admin = Auth::user();
        
        // Ensure user is admin
        if (!$admin->role === 'admin') {
            abort(403, 'Unauthorized access');
        }
        
        $request->validate([
            'admin_response' => 'required|string|min:10|max:1000'
        ]);
        
        $result = $reactivationRequest->reject(
            $admin->id, 
            $request->input('admin_response')
        );
        
        if ($result['success']) {
            // Log admin action
            Log::info("Admin rejected reactivation request: {$reactivationRequest->id}", [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'student_id' => $reactivationRequest->user_id,
                'subject_id' => $reactivationRequest->subject_id,
                'request_id' => $reactivationRequest->id,
                'rejection_reason' => $request->input('admin_response')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Reactivation request from {$reactivationRequest->user->name} has been rejected."
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Failed to reject reactivation request.'
        ], 500);
    }
    
    /**
     * Bulk approve multiple reactivation requests
     */
    public function bulkApproveRequests(Request $request)
    {
        $admin = Auth::user();
        
        // Ensure user is admin
        if (!$admin->role === 'admin') {
            abort(403, 'Unauthorized access');
        }
        
        $request->validate([
            'request_ids' => 'required|array',
            'request_ids.*' => 'integer|exists:reactivation_requests,id',
            'admin_response' => 'nullable|string|max:1000'
        ]);
        
        $requestIds = $request->input('request_ids');
        $adminResponse = $request->input('admin_response') ?? 'Request approved in bulk operation.';
        $successCount = 0;
        $failedCount = 0;
        
        DB::beginTransaction();
        
        try {
            foreach ($requestIds as $requestId) {
                $reactivationRequest = \App\Models\ReactivationRequest::find($requestId);
                
                if ($reactivationRequest && $reactivationRequest->status === 'pending') {
                    $result = $reactivationRequest->approve($admin->id, $adminResponse);
                    
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $failedCount++;
                    }
                }
            }
            
            DB::commit();
            
            // Log bulk approval
            Log::info("Admin performed bulk approval of reactivation requests", [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'request_ids' => $requestIds,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'admin_response' => $adminResponse
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "{$successCount} reactivation requests approved successfully. {$failedCount} failed."
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Bulk approval failed: ' . $e->getMessage(), [
                'admin_id' => $admin->id,
                'request_ids' => $requestIds,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to process bulk approval. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Get reactivation statistics for dashboard
     */
    public function reactivationStats()
    {
        $admin = Auth::user();
        
        // Ensure user is admin
        if (!$admin->role === 'admin') {
            abort(403, 'Unauthorized access');
        }
        
        $stats = [
            'total_requests' => \App\Models\ReactivationRequest::count(),
            'pending_requests' => \App\Models\ReactivationRequest::where('status', 'pending')->count(),
            'approved_requests' => \App\Models\ReactivationRequest::where('status', 'approved')->count(),
            'rejected_requests' => \App\Models\ReactivationRequest::where('status', 'rejected')->count(),
            'requests_today' => \App\Models\ReactivationRequest::whereDate('requested_at', today())->count(),
            'approvals_today' => \App\Models\ReactivationRequest::whereDate('reviewed_at', today())
                ->where('status', 'approved')
                ->count(),
            'avg_response_time' => \App\Models\ReactivationRequest::whereNotNull('reviewed_at')
                ->whereNotNull('requested_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, requested_at, reviewed_at)) as avg_hours')
                ->first()?->avg_hours ?? 0
        ];
        
        return response()->json($stats);
    }
}