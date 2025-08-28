<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ExamBan;
use App\Models\ReactivationRequest;

class SecurityViewController extends Controller
{
    /**
     * Show security violation detected page
     *
     * @return \Illuminate\View\View
     */
    public function violationDetected(Request $request)
    {
        $user = Auth::user();
        
        // If user is not authenticated, redirect to login with context
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please log in to view your security violation details.');
        }
        
        // ENHANCED: Multiple fallback methods to get subject context
        $subjectId = $request->get('subject_id');
        $subject = null;
        $ban = null;
        
        // Method 1: Direct subject_id parameter
        if ($subjectId) {
            $subject = \App\Models\Subject::find($subjectId);
            \Illuminate\Support\Facades\Log::info('Violation page: Found subject via parameter', [
                'subject_id' => $subjectId,
                'subject_name' => $subject ? $subject->name : 'Not found'
            ]);
        }
        
        // Method 2: Find active ban with subject relationship
        $ban = ExamBan::with('subject')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->when($subjectId, function($query) use ($subjectId) {
                return $query->where('subject_id', $subjectId);
            })
            ->orderBy('banned_at', 'desc')
            ->first();
            
        if ($ban && $ban->subject && !$subject) {
            $subject = $ban->subject;
            $subjectId = $ban->subject_id;
            \Illuminate\Support\Facades\Log::info('Violation page: Found subject via active ban', [
                'ban_id' => $ban->id,
                'subject_name' => $subject->name
            ]);
        }
        
        // Method 3: Recent violation for specific subject
        if (!$subject && $subjectId) {
            $recentViolation = \App\Models\ExamSecurityViolation::with('subject')
                ->where('user_id', $user->id)
                ->where('subject_id', $subjectId)
                ->orderBy('occurred_at', 'desc')
                ->first();
                
            if ($recentViolation && $recentViolation->subject) {
                $subject = $recentViolation->subject;
                \Illuminate\Support\Facades\Log::info('Violation page: Found subject via recent violation', [
                    'violation_id' => $recentViolation->id,
                    'subject_name' => $subject->name
                ]);
            }
        }
        
        // Method 4: Any recent violation to get ANY subject context
        if (!$subject) {
            $anyRecentViolation = \App\Models\ExamSecurityViolation::with('subject')
                ->where('user_id', $user->id)
                ->orderBy('occurred_at', 'desc')
                ->first();
                
            if ($anyRecentViolation && $anyRecentViolation->subject) {
                $subject = $anyRecentViolation->subject;
                $subjectId = $subject->id;
                \Illuminate\Support\Facades\Log::info('Violation page: Found subject via any recent violation', [
                    'violation_id' => $anyRecentViolation->id,
                    'subject_name' => $subject->name
                ]);
            }
        }
        
        // Method 5: Use first available subject if still no context
        if (!$subject && $user->class_id) {
            $subject = \App\Models\Subject::where('class_id', $user->class_id)->first();
            if ($subject) {
                $subjectId = $subject->id;
                \Illuminate\Support\Facades\Log::info('Violation page: Using first available subject', [
                    'subject_name' => $subject->name,
                    'class_id' => $user->class_id
                ]);
            }
        }
        
        // Final logging for debugging
        \Illuminate\Support\Facades\Log::info('Violation detected page final context', [
            'user_id' => $user->id,
            'subject_id_param' => $request->get('subject_id'),
            'final_subject_id' => $subjectId,
            'ban_found' => $ban ? $ban->id : null,
            'ban_subject' => $ban && $ban->subject ? $ban->subject->name : null,
            'final_subject_name' => $subject ? $subject->name : 'Still Unknown'
        ]);
        
        // If we found a subject but the URL doesn't have subject_id parameter, redirect with it
        // This ensures the URL always shows the subject context for bookmarking/sharing
        if ($subject && !$request->has('subject_id')) {
            return redirect()->route('security.violation-detected', ['subject_id' => $subject->id]);
        }
            
        return view('security.violation-detected', compact('ban', 'subject', 'subjectId'));
    }
    
    /**
     * Handle reactivation request submission
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function submitReactivationRequest(Request $request)
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'request_message' => 'required|string|min:50|max:1000'
        ]);
        
        $user = Auth::user();
        $subjectId = $request->subject_id;
        $subject = \App\Models\Subject::findOrFail($subjectId);
        
        // Check if user has an active ban for this subject
        $ban = ExamBan::where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->first();
            
        // If no ban exists, check if there are violations that should have created a ban
        if (!$ban) {
            $violations = \App\Models\ExamSecurityViolation::where('user_id', $user->id)
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
                    
                    \Illuminate\Support\Facades\Log::info('Created missing ban record during reactivation request', [
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
        $existingRequest = \App\Models\ReactivationRequest::where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('status', 'pending')
            ->first();
            
        if ($existingRequest) {
            return back()->withErrors(['error' => 'You already have a pending reactivation request for this subject.']);
        }
        
        // Create the reactivation request
        $result = \App\Models\ReactivationRequest::createRequest(
            $user->id,
            $subjectId,
            $ban->id,
            $request->request_message
        );
        
        if ($result['success']) {
            return redirect()->route('user.dashboard')
                ->with('success', 'Your reactivation request has been submitted successfully. An administrator will review your request as soon as possible.');
        } else {
            return back()->withErrors(['error' => $result['message']]);
        }
    }
    
    /**
     * Show critical security warning screen
     *
     * @return \Illuminate\View\View
     */
    public function criticalWarning(Request $request)
    {
        // Get subject information if provided
        $subjectId = $request->get('subject_id');
        $subject = null;
        
        if ($subjectId) {
            $subject = \App\Models\Subject::find($subjectId);
        }
        
        return view('security.critical-warning', compact('subject', 'subjectId'));
    }
}