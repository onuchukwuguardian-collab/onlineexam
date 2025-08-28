<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use App\Models\ReactivationRequest;
use Carbon\Carbon;

class SecurityController extends Controller
{
    /**
     * Report a security violation
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportViolation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'violation_type' => 'required|string',
            'description' => 'required|string',
            'exam_session_id' => 'nullable|exists:exam_sessions,id'
        ]);

        // First check if user is already banned
        $existingBan = ExamBan::isBannedFromSubject($request->user_id, $request->subject_id);
        if ($existingBan) {
            return response()->json([
                'success' => false,
                'banned' => true,
                'message' => 'User is already banned from this subject.',
                'ban_details' => $existingBan
            ]);
        }

        try {
            // Record violation
            $violation = ExamSecurityViolation::recordViolation(
                $request->user_id,
                $request->subject_id,
                $request->violation_type,
                $request->description,
                $request->exam_session_id
            );

            // Check if this violation should trigger a ban
            $shouldBan = false;
            $banDetails = null;

            // For tab switching - IMMEDIATE BAN (1-strike policy)
            if ($request->violation_type === ExamBan::VIOLATION_TAB_SWITCH) {
                $shouldBan = true;

                // Get all tab switch violations for this user and subject
                $violations = ExamSecurityViolation::where('user_id', $request->user_id)
                    ->where('subject_id', $request->subject_id)
                    ->where('violation_type', ExamBan::VIOLATION_TAB_SWITCH)
                    ->orderBy('occurred_at', 'desc')
                    ->get();
                
                // Create the ban
                $ban = ExamBan::createSubjectBan(
                    $request->user_id, 
                    $request->subject_id, 
                    ExamBan::VIOLATION_TAB_SWITCH, 
                    $violations
                );

                $banDetails = $ban;
            }

            return response()->json([
                'success' => true,
                'message' => 'Security violation recorded.',
                'banned' => $shouldBan,
                'ban_details' => $banDetails
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record security violation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record security violation.'
            ], 500);
        }
    }

    /**
     * Check if a user is banned from a subject
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkBanStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id'
        ]);

        $ban = ExamBan::isBannedFromSubject($request->user_id, $request->subject_id);

        return response()->json([
            'banned' => (bool) $ban,
            'ban_details' => $ban
        ]);
    }

    /**
     * Request reactivation after being banned
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestReactivation(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'exam_ban_id' => 'required|exists:exam_bans,id',
            'message' => 'required|string|min:10|max:1000'
        ]);

        // Check if user can request reactivation
        $canRequest = ReactivationRequest::canRequestReactivation(
            $request->user_id, 
            $request->subject_id
        );

        if (!$canRequest['can_request']) {
            return response()->json([
                'success' => false,
                'message' => $canRequest['reason']
            ]);
        }

        // Create reactivation request
        $result = ReactivationRequest::createRequest(
            $request->user_id,
            $request->subject_id,
            $request->exam_ban_id,
            $request->message
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reactivation request submitted successfully.',
            'request' => $result['request']
        ]);
    }
}