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

        // Get active bans for the user
        $activeBans = ExamBan::with('subject')
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->get();
            
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
}
