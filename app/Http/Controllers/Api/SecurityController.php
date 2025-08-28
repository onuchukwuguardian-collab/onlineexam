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

        try {
            // Record violation first
            ExamSecurityViolation::recordViolation(
                $request->user_id,
                $request->subject_id,
                $request->violation_type,
                $request->description,
                $request->exam_session_id
            );

            $isBanned = false;
            $banDetails = null;
            $violationCount = 0;

            // Implement 3-strike policy for tab switching
            if ($request->violation_type === ExamBan::VIOLATION_TAB_SWITCH) {

                $violations = ExamSecurityViolation::where('user_id', $request->user_id)
                    ->where('subject_id', $request->subject_id)
                    ->where('violation_type', ExamBan::VIOLATION_TAB_SWITCH)
                    ->orderBy('occurred_at', 'desc')
                    ->get();
                
                $violationCount = $violations->count();
                $banThreshold = 3;

                if ($violationCount >= $banThreshold) {
                    $isBanned = true;

                    // Use update-or-create logic to handle bans
                    $ban = ExamBan::where('user_id', $request->user_id)
                                  ->where('subject_id', $request->subject_id)
                                  ->first();

                    if ($ban) {
                        if (!$ban->is_active) {
                            $banDetails = $ban->reactivateAndRecordViolation(
                                ExamBan::VIOLATION_TAB_SWITCH,
                                $violations
                            );
                        } else {
                            $banDetails = $ban;
                        }
                    } else {
                        $banDetails = ExamBan::createSubjectBan(
                            $request->user_id,
                            $request->subject_id,
                            ExamBan::VIOLATION_TAB_SWITCH,
                            $violations
                        );
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Security violation recorded.',
                'banned' => $isBanned,
                'violation_count' => $violationCount,
                'ban_details' => $banDetails
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record security violation or process ban: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the security violation.'
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