<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Subject;
use App\Models\UserScore;
use App\Models\ClassModel; // Assuming this is your Class model name
use App\Models\ResetLog;   // Your ResetLog model
use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;

class UserDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        // Eager load classModel if it's frequently accessed in the view
        $user->load('classModel');

        if (!$user->classModel) {
            // This check should ideally be robustly handled during login,
            // but as a fallback, prevent access to dashboard if no class is assigned.
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Your account is not properly configured (no class assigned). Please contact an administrator.');
        }

        // Fetch subjects available for the user's specific class_id
        $availableSubjects = Subject::where('class_id', $user->class_id)
            ->orderBy('name')
            ->get();

        // Scores for the subject cards (latest score for each subject taken)
        $takenScoresArray = UserScore::where('user_id', $user->id)
            ->orderBy('subject_id')
            ->orderBy('submission_time', 'desc') // Get the latest score if a subject was taken multiple times
            ->get()
            ->keyBy('subject_id') // Keys by subject_id, but keeps the latest UserScore object
            ->map(function ($userScore) {
                return $userScore->score; // We only need the score value for the cards
            }); // This results in [subject_id => score]

        $countTakenSubjects = $takenScoresArray->count();

        // Subject Limit Logic (copied from previous response, ensure config/exams.php exists or defaults are fine)
        $subjectLimitsConfig = config('exams.subject_limits', [
            'JSS' => 11,
            'SS1' => 16,
            'SS_OTHERS' => 9
        ]);
        $currentSubjectLimit = 0;
        $levelGroup = strtoupper($user->classModel->level_group ?? ''); // Null safe for level_group
        $className = strtoupper($user->classModel->name ?? '');     // Null safe for class name

        if ($levelGroup === 'JSS') {
            $currentSubjectLimit = $subjectLimitsConfig['JSS'] ?? 0;
        } elseif ($levelGroup === 'SS') {
            if ($className === 'SS1') {
                $currentSubjectLimit = $subjectLimitsConfig['SS1'] ?? 0;
            } elseif (in_array($className, ['SS2', 'SS3'])) {
                $currentSubjectLimit = $subjectLimitsConfig['SS_OTHERS'] ?? 0;
            }
        }
        $limitReached = ($currentSubjectLimit > 0 && $countTakenSubjects >= $currentSubjectLimit);

        // Reset counts for each subject for this user
        $resetCounts = ResetLog::where('user_id', $user->id)
            ->select('subject_id', DB::raw('count(*) as total_resets'))
            ->groupBy('subject_id')
            ->pluck('total_resets', 'subject_id');

        // Fetch ALL scores for the "My Scores" modal/section (this is the key part for your request)
        $allUserScores = UserScore::with([
            'subject' => function ($query) {
                $query->select('id', 'name'); // Eager load only necessary subject details
            }
        ])
            ->where('user_id', $user->id)
            ->orderBy('submission_time', 'desc') // Show most recent scores first
            ->get(); // Get a collection of UserScore objects

        // FOR DEBUGGING - Remove after testing
        // dd($allUserScores->toArray(), $user->id); 

        // FIXED: Comprehensive ban checking and automatic ban creation
        // Step 1: Get existing active bans
        $activeBans = ExamBan::with('subject')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
            
        // Step 2: Check for violations that should trigger bans but haven't been created yet
        $this->createMissingBans($user->id);
        
        // Step 3: Refresh active bans after creating missing ones
        $activeBans = ExamBan::with('subject')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
            
        // Step 4: Format bans for display
        $violationBasedBans = $activeBans->map(function($ban) {
            return [
                'subject_id' => $ban->subject_id,
                'subject_name' => $ban->subject->name ?? 'Unknown Subject',
                'ban_type' => $ban->violation_type ?? 'security_violation',
                'reason' => $ban->ban_reason,
                'banned_at' => $ban->banned_at,
                'ban_id' => $ban->id
            ];
        })->toArray();
        
        $hasActiveBans = $activeBans->count() > 0; 

        return view('user.dashboard', compact(
            'user',
            'availableSubjects',
            'takenScoresArray', // Renamed to avoid confusion with $allUserScores
            'currentSubjectLimit',
            'countTakenSubjects',
            'limitReached',
            'resetCounts',
            'allUserScores', // This collection is for the "My Scores" modal/section
            'activeBans',
            'violationBasedBans',
            'hasActiveBans'
        ));
    }

    public function getScoresModal()
    {
        $user = Auth::user();
        
        // Fetch ALL scores for the user
        $allUserScores = UserScore::with([
            'subject' => function ($query) {
                $query->select('id', 'name');
            }
        ])
            ->where('user_id', $user->id)
            ->orderBy('submission_time', 'desc')
            ->get();

        if ($allUserScores->isEmpty()) {
            $html = '
                <div class="empty-scores">
                    <div class="empty-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3>No Scores Yet</h3>
                    <p>You haven\'t completed any exams yet. Start taking exams to see your scores here!</p>
                </div>
            ';
        } else {
            $grandTotalScored = 0;
            $grandTotalPossible = 0;
            
            $html = '<div class="scores-table-container">
                <table class="scores-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Points Scored</th>
                            <th>Total Points</th>
                            <th>Performance</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>';
            
            foreach ($allUserScores as $scoreEntry) {
                $percentage = $scoreEntry->total_questions > 0 ? round(($scoreEntry->score / $scoreEntry->total_questions) * 100, 1) : 0;
                $performanceClass = $percentage >= 50 ? 'percentage-pass' : 'percentage-fail';
                $performanceText = $percentage >= 80 ? 'Excellent' : ($percentage >= 70 ? 'Very Good' : ($percentage >= 60 ? 'Good' : ($percentage >= 50 ? 'Fair' : 'Needs Improvement')));
                $subjectName = $scoreEntry->subject ? $scoreEntry->subject->name : 'Subject Deleted';
                $date = $scoreEntry->submission_time ? \Carbon\Carbon::parse($scoreEntry->submission_time)->format('M d, Y') : ($scoreEntry->created_at ? $scoreEntry->created_at->format('M d, Y') : 'N/A');
                
                $html .= "
                    <tr>
                        <td class=\"subject-name\">
                            <div class=\"subject-info\">
                                <i class=\"fas fa-book subject-icon\"></i>
                                <span>{$subjectName}</span>
                            </div>
                        </td>
                        <td class=\"score-cell\">
                            <span class=\"score-number\">{$scoreEntry->score} points</span>
                        </td>
                        <td class=\"total-cell\">{$scoreEntry->total_questions} points</td>
                        <td class=\"performance-cell\">
                            <span class=\"performance-badge {$performanceClass}\">
                                {$performanceText} ({$percentage}%)
                            </span>
                        </td>
                        <td class=\"date-cell\">{$date}</td>
                    </tr>
                ";
                
                $grandTotalScored += $scoreEntry->score;
                if ($scoreEntry->total_questions > 0) {
                    $grandTotalPossible += $scoreEntry->total_questions;
                }
            }
            
            if ($grandTotalPossible > 0) {
                $overallPercentage = round(($grandTotalScored / $grandTotalPossible) * 100, 1);
                $overallPerformance = $overallPercentage >= 80 ? 'Excellent' : ($overallPercentage >= 70 ? 'Very Good' : ($overallPercentage >= 60 ? 'Good' : ($overallPercentage >= 50 ? 'Fair' : 'Needs Improvement')));
                $html .= "
                    </tbody>
                    <tfoot>
                        <tr class=\"summary-row\">
                            <td class=\"summary-label\">Overall Total:</td>
                            <td class=\"summary-score\">{$grandTotalScored} points</td>
                            <td class=\"summary-total\">{$grandTotalPossible} points</td>
                            <td class=\"summary-performance\" colspan=\"2\">
                                <span class=\"performance-badge percentage-summary\">
                                    {$overallPerformance} ({$overallPercentage}%)
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                ";
            } else {
                $html .= '</tbody>';
            }
            
            $html .= '</table></div>';
        }

        return response()->json([
            'success' => true,
            'html' => $html
        ]);
    }
    
    /**
     * Create missing ban records for violations that should trigger bans
     * CRITICAL: This ensures dashboard display matches actual ban enforcement
     * FIXED: Only creates bans for violations after last reactivation
     */
    private function createMissingBans($userId)
    {
        try {
            DB::beginTransaction();
            
            // Get the last reactivation time for each subject for this specific user
            $lastReactivations = DB::table('exam_bans')
                ->select('subject_id', DB::raw('MAX(reactivated_at) as last_reactivated'))
                ->where('user_id', $userId)
                ->whereNotNull('reactivated_at')
                ->groupBy('subject_id')
                ->pluck('last_reactivated', 'subject_id');
            
            // Check for tab switch violations (immediate ban) - ONLY AFTER LAST REACTIVATION
            $tabSwitchViolations = DB::table('exam_security_violations as v')
                ->select('v.subject_id', DB::raw('COUNT(*) as violation_count'), DB::raw('MAX(v.occurred_at) as latest_violation'))
                ->where('v.user_id', $userId)
                ->where('v.violation_type', 'tab_switch')
                ->where(function($query) use ($lastReactivations, $userId) {
                    // Only include violations that occurred after the last reactivation
                    foreach ($lastReactivations as $subjectId => $lastReactivated) {
                        $query->orWhere(function($subQuery) use ($subjectId, $lastReactivated) {
                            $subQuery->where('v.subject_id', $subjectId)
                                     ->where('v.occurred_at', '>', $lastReactivated);
                        });
                    }
                    
                    // For subjects without any reactivation history, include all violations
                    $subjectsWithReactivations = array_keys($lastReactivations->toArray());
                    if (!empty($subjectsWithReactivations)) {
                        $query->orWhereNotIn('v.subject_id', $subjectsWithReactivations);
                    }
                })
                ->whereNotExists(function($query) use ($userId) {
                    $query->select(DB::raw(1))
                          ->from('exam_bans')
                          ->where('exam_bans.user_id', $userId)
                          ->whereColumn('exam_bans.subject_id', 'v.subject_id')
                          ->where('exam_bans.is_active', true);
                })
                ->groupBy('v.subject_id')
                ->get();
                
            // Check for right-click violations (15+ strikes) - ONLY AFTER LAST REACTIVATION
            $rightClickViolations = DB::table('exam_security_violations as v')
                ->select('v.subject_id', DB::raw('COUNT(*) as violation_count'), DB::raw('MAX(v.occurred_at) as latest_violation'))
                ->where('v.user_id', $userId)
                ->where('v.violation_type', 'right_click')
                ->where(function($query) use ($lastReactivations, $userId) {
                    // Only include violations that occurred after the last reactivation
                    foreach ($lastReactivations as $subjectId => $lastReactivated) {
                        $query->orWhere(function($subQuery) use ($subjectId, $lastReactivated) {
                            $subQuery->where('v.subject_id', $subjectId)
                                     ->where('v.occurred_at', '>', $lastReactivated);
                        });
                    }
                    
                    // For subjects without any reactivation history, include all violations
                    $subjectsWithReactivations = array_keys($lastReactivations->toArray());
                    if (!empty($subjectsWithReactivations)) {
                        $query->orWhereNotIn('v.subject_id', $subjectsWithReactivations);
                    }
                })
                ->whereNotExists(function($query) use ($userId) {
                    $query->select(DB::raw(1))
                          ->from('exam_bans')
                          ->where('exam_bans.user_id', $userId)
                          ->whereColumn('exam_bans.subject_id', 'v.subject_id')
                          ->where('exam_bans.is_active', true);
                })
                ->groupBy('v.subject_id')
                ->havingRaw('COUNT(*) >= 15')
                ->get();
                
            // Create bans for tab switch violations (only for violations after reactivation)
            foreach ($tabSwitchViolations as $violation) {
                $this->createBanRecord($userId, $violation->subject_id, 'tab_switch', $violation->violation_count, $violation->latest_violation);
            }
            
            // Create bans for right-click violations (only for violations after reactivation)
            foreach ($rightClickViolations as $violation) {
                $this->createBanRecord($userId, $violation->subject_id, 'right_click', $violation->violation_count, $violation->latest_violation);
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create missing bans: ' . $e->getMessage());
        }
    }
    
    /**
     * Create a ban record for a specific violation
     */
    private function createBanRecord($userId, $subjectId, $violationType, $violationCount, $latestViolation)
    {
        $user = \App\Models\User::find($userId);
        $subject = \App\Models\Subject::find($subjectId);
        
        if (!$user || !$subject) {
            return;
        }
        
        // Get all violations for this user and subject
        $violations = ExamSecurityViolation::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->where('violation_type', $violationType)
            ->orderBy('occurred_at', 'desc')
            ->get();
            
        // Check for previous bans to determine ban count
        $previousBans = ExamBan::where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->count();
            
        $banCount = $previousBans + 1;
        
        // Create ban reason
        $banReason = '';
        if ($violationType === 'tab_switch') {
            $banReason = "🚫 IMMEDIATE BAN: Tab switching detected during exam. This violates exam integrity rules. Contact administrator for reactivation.";
        } elseif ($violationType === 'right_click') {
            $banReason = "🚫 15-STRIKE BAN: {$violationCount} right-click violations detected. This violates exam security policy. Contact administrator for reactivation.";
        }
        
        // Create the ban record
        ExamBan::create([
            'user_id' => $userId,
            'subject_id' => $subjectId,
            'ban_reason' => $banReason,
            'violation_details' => json_encode($violations->map(function($v) {
                return [
                    'type' => $v->violation_type,
                    'description' => $v->description,
                    'occurred_at' => $v->occurred_at->toISOString(),
                    'user_agent' => $v->user_agent,
                    'ip_address' => $v->ip_address
                ];
            })->toArray()),
            'violation_type' => $violationType,
            'total_violations' => $violationCount,
            'banned_at' => $latestViolation,
            'is_permanent' => true,
            'is_active' => true,
            'ban_count' => $banCount,
            'admin_notes' => "AUTO-CREATED BAN: Student {$user->name} (Reg: {$user->registration_number}) - Banned after {$violationCount} {$violationType} violation(s) for {$subject->name} only. Ban #{$banCount} for this subject."
        ]);
        
        \Log::info("Auto-created missing ban record", [
            'user_id' => $userId,
            'subject_id' => $subjectId,
            'violation_type' => $violationType,
            'violation_count' => $violationCount,
            'ban_count' => $banCount
        ]);
    }
}
