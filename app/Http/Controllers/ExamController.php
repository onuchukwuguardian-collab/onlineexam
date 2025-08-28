<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Subject;
use App\Models\Question;
use App\Models\UserScore;
use App\Models\UserAnswer;
use App\Models\Option; // For fetching all options
use App\Models\ExamSession;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use App\Services\ViolationDetectionService;
use Carbon\Carbon;
use Exception;

class ExamController extends Controller
{
    public function start(Subject $subject)
    {
        // Extend session lifetime for exam duration + buffer
        $examDurationMinutes = $subject->exam_duration_minutes ?? 60;
        $sessionLifetimeMinutes = max(180, $examDurationMinutes + 30); // At least 3 hours or exam duration + 30 min buffer
        config(['session.lifetime' => $sessionLifetimeMinutes]);
        
        $user = Auth::user();

        // Additional security check - ensure subject belongs to user's class
        if ($subject->class_id !== $user->class_id) {
            abort(403, 'Unauthorized access to this exam.');
        }

        // Use our new security system instead of this check
        // CRITICAL: Subject-Specific Ban Check - Each subject operates independently
        // Ban checks will be handled by the client-side security system and SecurityController

        // Clear any previous session messages to prevent confusion after reset
        session()->forget(['message', 'error']);

        // Check if already fully submitted and scored (but not reset)
        $existingScore = UserScore::where('user_id', $user->id)
            ->where('subject_id', $subject->id)
            ->first();
        
        // Check for existing active exam session
        $existingSession = ExamSession::where('user_id', $user->id)
            ->where('subject_id', $subject->id)
            ->where('is_active', true)
            ->first();
            
        // Clean up any expired sessions that might be lingering
        if ($existingSession && $existingSession->isExpired()) {
            $existingSession->markAsCompleted(true);
            $existingSession = null;
        }

        // If there's a score but no active session, exam is completed
        if ($existingScore && !$existingSession) {
            return redirect()->route('user.dashboard')->with('message', 'You have already completed this exam.');
        }
        
        // Additional check: If admin has reset this user's progress, ensure clean state
        if (!$existingScore && !$existingSession) {
            // Clean up any lingering inactive sessions that might cause issues
            ExamSession::where('user_id', $user->id)
                ->where('subject_id', $subject->id)
                ->where('is_active', false)
                ->delete();
                
            // Clear any cached exam data that might interfere with fresh start
            Cache::forget("exam_session_{$user->id}_{$subject->id}");
            Cache::forget("exam_timer_{$user->id}_{$subject->id}");
            Cache::forget("exam_progress_{$user->id}_{$subject->id}");
        }

        if ($existingSession) {
            // Check if session has expired
            if ($existingSession->isExpired()) {
                // If there's already a score, just clean up the session
                if ($existingScore) {
                    $existingSession->markAsCompleted(true);
                    return redirect()->route('user.dashboard')->with('message', 'You have already completed this exam.');
                } else {
                    // Auto-submit the expired exam only if no score exists and user still exists
                    if ($user && $user->exists) {
                        $this->autoSubmitExpiredExam($existingSession);
                        return redirect()->route('user.dashboard')->with('message', 'Your previous exam session has been automatically submitted due to time expiration. Your answers have been saved and scored.');
                    } else {
                        // Clean up orphaned session
                        $existingSession->delete();
                        return redirect()->route('user.dashboard');
                    }
                }
            }
            
            // Check if student was logged out due to security violation
            $recentViolations = ExamSecurityViolation::getRecentViolations($user->id, $subject->id, 1);
            
            if ($recentViolations->count() > 0) {
                // Redirect to critical warning page with proper reactivation flow
                return redirect()->route('security.critical.warning')->with('error', 
                    '🚫 SECURITY VIOLATION: You have been banned from this subject. Please use the reactivation button to request access.'
                );
            }
            
            // Check if session is about to expire (within 5 minutes)
            if ($existingSession->remaining_time <= 300) {
                // Update last activity to prevent premature expiration
                $existingSession->updateActivity();
            }
            
            // Resume existing session (either normal or reset due to violation)
            $examSession = $existingSession;
        } else {
            // Create new exam session using the safer method
            $now = Carbon::now();
            
            // Ensure completely fresh start by clearing any potential cache
            Cache::forget("exam_session_{$user->id}_{$subject->id}");
            Cache::forget("exam_timer_{$user->id}_{$subject->id}");
            Cache::forget("exam_progress_{$user->id}_{$subject->id}");
            
            $examSession = ExamSession::createSafely([
                'user_id' => $user->id,
                'subject_id' => $subject->id,
                'started_at' => $now,
                'expires_at' => $now->copy()->addMinutes($subject->exam_duration_minutes),
                'duration_minutes' => $subject->exam_duration_minutes,
                'answers' => [],
                'current_question_index' => 0,
                'is_active' => true,
                'last_activity_at' => $now
            ]);
            
            Log::info("Created fresh exam session for user {$user->id}, subject {$subject->id}, duration {$subject->exam_duration_minutes} minutes");
        }

        $questions = Question::with('options')->where('subject_id', $subject->id)->get();
        if ($questions->isEmpty()) {
            return redirect()->route('user.dashboard')->with('error', 'No questions found for this subject.');
        }

        // Prepare questions for JavaScript
        $questionsList = $questions->map(function ($question) {
            return [
                'id' => $question->id,
                'text' => $question->question_text,
                'image_path' => $question->image_path ? asset('storage/' . $question->image_path) : null,
                'options' => $question->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'letter' => $option->option_letter,
                        'text' => $option->option_text,
                    ];
                })->toArray(),
            ];
        });

        return view('user.exam_simple', compact(
            'subject',
            'questionsList',
            'examSession'
        ));
    }

    public function submit(Request $request)
    {
        $user = Auth::user();
        
        // Debug logging
        Log::info("Exam submission attempt by user {$user->id}", [
            'request_data' => $request->all(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);
        
        // Validate input data
        $validated = $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'answers' => 'nullable|array',
            'answers.*' => 'string|in:A,B,C,D,E',
            'session_id' => 'required|integer|exists:exam_sessions,id',
            'auto_submitted' => 'boolean'
        ]);
        
        $subjectId = $validated['subject_id'];
        $submittedAnswers = $validated['answers'] ?? [];
        $sessionId = $validated['session_id'];
        $autoSubmitted = $validated['auto_submitted'] ?? false;

        $subject = Subject::findOrFail($subjectId);
        
        // Get the exam session
        $examSession = ExamSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->first();

        if (!$examSession) {
            return redirect()->route('user.dashboard')->with('error', 'Invalid exam session.');
        }

        // Prevent re-submission (double check)
        if (UserScore::where('user_id', $user->id)->where('subject_id', $subjectId)->exists()) {
            return redirect()->route('user.score.display', $subjectId)->with('message', 'Exam already submitted.');
        }

        $questions = Question::where('subject_id', $subjectId)->get()->keyBy('id');
        $score = 0;
        $totalQuestions = $questions->count();
        $userAnswerRecords = [];

        DB::beginTransaction();
        try {
            if (is_array($submittedAnswers)) {
                foreach ($submittedAnswers as $questionId => $selectedLetter) {
                    $question = $questions->get((int) $questionId);
                    if ($question) {
                        $isCorrect = (strtoupper(trim($selectedLetter)) === strtoupper(trim($question->correct_answer)));
                        if ($isCorrect) {
                            $score++;
                        }
                        $userAnswerRecords[] = [
                            'user_id' => $user->id,
                            'question_id' => $question->id,
                            'selected_option_letter' => $selectedLetter,
                            'is_correct' => $isCorrect,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
            
            if (!empty($userAnswerRecords)) {
                UserAnswer::insert($userAnswerRecords);
            }

            // Calculate time taken from session
            $timeTakenSeconds = Carbon::now()->diffInSeconds($examSession->started_at);

            $userScore = UserScore::create([
                'user_id' => $user->id,
                'subject_id' => $subjectId,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'time_taken_seconds' => $timeTakenSeconds,
                'submission_time' => now(),
            ]);

            // Mark exam session as completed
            $examSession->markAsCompleted($autoSubmitted);

            DB::commit();

            // Store in session for immediate display on score page
            session([
                'last_exam_score' => $score,
                'last_exam_total' => $totalQuestions,
                'last_exam_subject_name' => $subject->name,
                'auto_submitted' => $autoSubmitted,
                'exam_just_submitted' => true
            ]);

            return redirect()->route('user.score.display', $subjectId);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('user.exam.start', $subjectId)
                ->with('error', 'An error occurred while submitting your exam. Please try again.');
        }
    }

    private function autoSubmitExpiredExam(ExamSession $examSession)
    {
        $user = $examSession->user;
        $subject = $examSession->subject;
        
        // Check if user and subject still exist
        if (!$user || !$subject) {
            $examSession->delete();
            return;
        }
        
        // Check if already submitted
        if (UserScore::where('user_id', $user->id)->where('subject_id', $subject->id)->exists()) {
            $examSession->markAsCompleted(true);
            return;
        }

        $questions = Question::where('subject_id', $subject->id)->get()->keyBy('id');
        $submittedAnswers = $examSession->answers ?? [];
        $score = 0;
        $totalQuestions = $questions->count();
        $userAnswerRecords = [];

        DB::beginTransaction();
        try {
            if (is_array($submittedAnswers)) {
                foreach ($submittedAnswers as $questionId => $selectedLetter) {
                    $question = $questions->get((int) $questionId);
                    if ($question) {
                        $isCorrect = (strtoupper(trim($selectedLetter)) === strtoupper(trim($question->correct_answer)));
                        if ($isCorrect) {
                            $score++;
                        }
                        $userAnswerRecords[] = [
                            'user_id' => $user->id,
                            'question_id' => $question->id,
                            'selected_option_letter' => $selectedLetter,
                            'is_correct' => $isCorrect,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
            
            if (!empty($userAnswerRecords)) {
                UserAnswer::insert($userAnswerRecords);
            }

            $timeTakenSeconds = $examSession->duration_minutes * 60; // Full duration used

            UserScore::create([
                'user_id' => $user->id,
                'subject_id' => $subject->id,
                'score' => $score,
                'total_questions' => $totalQuestions,
                'time_taken_seconds' => $timeTakenSeconds,
                'submission_time' => now(),
            ]);

            $examSession->markAsCompleted(true);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Auto-submit failed for exam session ' . $examSession->id . ': ' . $e->getMessage());
        }
    }

    public function displayScore(Subject $subject)
    {
        $user = Auth::user();
        $userScore = UserScore::where('user_id', $user->id) // Changed variable name
            ->where('subject_id', $subject->id)
            ->latest('submission_time')
            ->first();

        if (!$userScore && !session()->has('last_exam_score')) {
            return redirect()->route('user.dashboard')->with('error', 'No score found for this exam.');
        }

        $displayScore = $userScore ? $userScore->score : session('last_exam_score');
        $displayTotal = $userScore ? $userScore->total_questions : session('last_exam_total');
        $displaySubjectName = $subject->name;

        // Pass the $userScore object to the view for the review link
        return view('user.score_display', compact('displayScore', 'displayTotal', 'displaySubjectName', 'subject', 'userScore'));
    }

    public function keepAlive()
    {
        // This endpoint is just to keep the PHP session alive.
        // Session is automatically "touched" by Laravel middleware.
        return response()->json(['status' => 'session_active']);
    }

    public function syncTime(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'session_id' => 'required|integer|exists:exam_sessions,id'
        ]);

        $examSession = ExamSession::where('id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$examSession) {
            return response()->json(['error' => 'Invalid exam session'], 404);
        }

        // Update last activity
        $examSession->updateActivity();
        
        return response()->json([
            'remaining_time' => $examSession->actual_remaining_time,
            'is_expired' => $examSession->isExpired(),
            'server_time' => now()->toISOString()
        ]);
    }

    public function saveProgress(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'session_id' => 'required|integer|exists:exam_sessions,id',
            'answers' => 'nullable|array',
            'current_question_index' => 'integer|min:0'
        ]);

        $examSession = ExamSession::where('id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$examSession) {
            return response()->json(['error' => 'Invalid exam session'], 400);
        }

        if ($examSession->isExpired()) {
            $this->autoSubmitExpiredExam($examSession);
            return response()->json(['expired' => true, 'message' => 'Exam time expired']);
        }

        $examSession->saveProgress(
            $validated['answers'] ?? [],
            $validated['current_question_index'] ?? 0
        );

        return response()->json([
            'status' => 'progress_saved',
            'remaining_time' => $examSession->remaining_time
        ]);
    }

    public function checkTimer(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'session_id' => 'required|integer|exists:exam_sessions,id'
        ]);

        $examSession = ExamSession::where('id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$examSession) {
            return response()->json(['error' => 'Invalid exam session'], 400);
        }

        if ($examSession->isExpired()) {
            $this->autoSubmitExpiredExam($examSession);
            return response()->json(['expired' => true, 'message' => 'Exam time expired']);
        }

        $examSession->updateActivity();

        return response()->json([
            'remaining_time' => $examSession->remaining_time,
            'is_active' => true
        ]);
    }



    public function review(UserScore $userScore) // Route model binding
    {
        $user = Auth::user();

        // Ensure the score belongs to the currently authenticated user
        if ($userScore->user_id !== $user->id) {
            return redirect()->route('user.dashboard')->with('error', 'You are not authorized to view these answers.');
        }

        $subject = $userScore->subject()->first(); // Get the subject
        if (!$subject) {
            return redirect()->route('user.dashboard')->with('error', 'Subject not found for this score.');
        }

        // Fetch all questions for the subject, with their options and the user's answer for each
        $questions = Question::with(['options'])
            ->where('subject_id', $userScore->subject_id)
            ->orderBy('id') // Or however they were ordered in the exam
            ->get();

        // Get user's answers for these questions in an associative array for easy lookup
        $userAnswers = UserAnswer::where('user_id', $user->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->get()
            ->keyBy('question_id'); // Key by question_id for easy access in the view

        return view('user.exam_review', compact('userScore', 'subject', 'questions', 'userAnswers'));
    }

    /**
     * 🚫 PROFESSIONAL SUBJECT-SPECIFIC VIOLATION TRACKING SYSTEM
     * 
     * STRICT RULES:
     * 1. Each subject is completely isolated (Basic Science ≠ Cultural Arts)
     * 2. Violations are tracked per student per specific subject
     * 3. Bans are subject-specific (banned from Math ≠ banned from English)
     * 4. Only administrators can reactivate banned students
     * 5. Students tracked by Registration Number + Email (NOT IP address)
     */
    public function recordSecurityViolation(Request $request)
    {
        $user = Auth::user();
        
        // Return a success response without actually recording anything
        // This ensures old code calling this method doesn't break, but
        // our new security system handles everything properly
        
        return response()->json([
            'violation_recorded' => true,
            'message' => 'Security handling moved to new system',
            'should_lock' => false
        ]);
    }

    /**
     * Get subject-specific violation messages
     */
    private function getSubjectSpecificViolationMessage($violationType, $count, $subject, $user = null)
    {
        $userInfo = $user ? "Student: {$user->name} (Reg: {$user->registration_number})" : "Student";
        $subjectName = $subject->name;

        switch ($violationType) {
            case 'tab_switch':
                return "⚠️ IMMEDIATE BAN WARNING FOR {$subjectName} ({$userInfo}): Tab switching detected in {$subjectName}. This is violation #{$count} - YOU WILL BE PERMANENTLY BANNED IMMEDIATELY.";

            case 'tab_switch_attempt':
                return "⚠️ IMMEDIATE BAN WARNING FOR {$subjectName} ({$userInfo}): Tab switch attempt blocked in {$subjectName}. This is violation #{$count} - YOU WILL BE PERMANENTLY BANNED IMMEDIATELY.";

            case 'right_click':
                // RIGHT-CLICK: Always warnings only, never bans
                return "⚠️ RIGHT-CLICK WARNING #{$count} IN {$subjectName} ({$userInfo}): Right-click detected in {$subjectName}. Please do not right-click during exams.";


            case 'copy_attempt':
                if ($count >= 3) {
                    return "🚫 COPY-PASTE BAN FROM {$subjectName} ({$userInfo}): 3rd copy attempt in {$subjectName}. You are now PERMANENTLY BANNED from {$subjectName}.";
                } else {
                    $remaining = 3 - $count;
                    return "⚠️ COPY-PASTE WARNING #{$count}/3 IN {$subjectName} ({$userInfo}): Copy attempt detected in {$subjectName}. {$remaining} more attempts = permanent ban.";
                }

            case 'dev_tools':
                if ($count >= 3) {
                    return "🚫 DEV-TOOLS BAN FROM {$subjectName} ({$userInfo}): 3rd dev tools access in {$subjectName}. You are now PERMANENTLY BANNED from {$subjectName}.";
                } else {
                    $remaining = 3 - $count;
                    return "⚠️ DEV-TOOLS WARNING #{$count}/3 IN {$subjectName} ({$userInfo}): Developer tools detected in {$subjectName}. {$remaining} more attempts = permanent ban.";
                }

            default:
                if ($count >= 3) {
                    return "🚫 SECURITY BAN FROM {$subjectName} ({$userInfo}): 3rd security violation in {$subjectName}. You are now PERMANENTLY BANNED.";
                } else {
                    $remaining = 3 - $count;
                    return "⚠️ SECURITY WARNING #{$count}/3 IN {$subjectName} ({$userInfo}): Security violation detected. {$remaining} more violations = permanent ban.";
                }
        }
    }

    /**
     * 🚫 EXECUTE SUBJECT-SPECIFIC BAN (Professional Implementation)
     * This method creates a ban that ONLY affects the specific subject
     */
    private function executeSubjectSpecificBan($user, $subjectId, $examSession, $violation, $request)
    {
        DB::beginTransaction();
        
        try {
            $subject = Subject::findOrFail($subjectId);
            
            // Check if user already has an active ban for this SPECIFIC subject
            $existingBan = ExamBan::where('user_id', $user->id)
                ->where('subject_id', $subjectId) // SUBJECT-SPECIFIC CHECK
                ->where('is_active', true)
                ->first();
            
            if ($existingBan) {
                Log::info("User {$user->id} already banned from subject {$subjectId}, skipping duplicate ban creation");
                DB::rollBack();
                return;
            }
            
            // Get all violations for this user and THIS SPECIFIC SUBJECT
            $subjectViolations = ExamSecurityViolation::where('user_id', $user->id)
                ->where('subject_id', $subjectId) // ONLY this subject's violations
                ->get();
            
            // Auto-submit the exam with current answers
            $this->autoSubmitViolatedExam($examSession, $user, $subjectId);
            
            // Create SUBJECT-SPECIFIC ban with detailed tracking
            $ban = ExamBan::create([
                'user_id' => $user->id,
                'subject_id' => $subjectId, // CRITICAL: Subject-specific ban
                'ban_reason' => "🚫 SUBJECT-SPECIFIC BAN: Permanently banned from {$subject->name} due to security violations. Other subjects remain accessible. Only admin can reactivate for {$subject->name}.",
                'violation_details' => $subjectViolations->map(function ($v) use ($user, $subject) {
                    return [
                        'violation_id' => $v->id,
                        'type' => $v->violation_type,
                        'description' => $v->description,
                        'occurred_at' => $v->occurred_at->toISOString(),
                        'subject_specific_tracking' => [
                            'banned_subject_only' => $subject->name,
                            'subject_id' => $subject->id,
                            'other_subjects_not_affected' => true,
                            'student_registration' => $user->registration_number,
                            'student_email' => $user->email,
                            'tracking_method' => 'registration_and_email_based'
                        ],
                        'metadata' => $v->metadata
                    ];
                })->toArray(),
                'total_violations' => $subjectViolations->count(),
                'banned_at' => now(),
                'is_permanent' => true,
                'is_active' => true,
                'admin_notes' => "SUBJECT-SPECIFIC BAN: Student {$user->name} (Reg: {$user->registration_number}, Email: {$user->email}) banned from {$subject->name} ONLY. Other subjects remain accessible. Tracked by registration number and email, NOT IP address."
            ]);
            
            // Mark exam session as completed due to violation
            $examSession->markAsCompleted(true);
            
            // 📊 CRITICAL AUDIT LOG
            Log::critical("SUBJECT-SPECIFIC BAN EXECUTED", [
                'ban_type' => 'subject_specific_isolation',
                'ban_id' => $ban->id,
                'student_identification' => [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email, // Primary identifier
                    'registration_number' => $user->registration_number, // Primary identifier
                    'tracking_method' => 'registration_and_email_based'
                ],
                'ban_scope' => [
                    'banned_from_subject' => $subject->name,
                    'banned_subject_id' => $subjectId,
                    'other_subjects_accessible' => true,
                    'isolation_confirmed' => true
                ],
                'violation_summary' => [
                    'total_violations_in_this_subject' => $subjectViolations->count(),
                    'violation_types' => $subjectViolations->pluck('violation_type')->unique()->toArray(),
                    'first_violation_date' => $subjectViolations->min('occurred_at'),
                    'last_violation_date' => $subjectViolations->max('occurred_at')
                ],
                'admin_requirements' => [
                    'reactivation_method' => 'admin_only',
                    'reactivation_scope' => "Only affects {$subject->name}, other subjects unaffected",
                    'contact_instruction' => 'Student must contact administrator for reactivation'
                ],
                'system_integrity' => [
                    'exam_auto_submitted' => true,
                    'session_completed' => true,
                    'ban_active' => true,
                    'other_subjects_protected' => true
                ]
            ]);
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to execute subject-specific ban for user {$user->id}, subject {$subjectId}: {$e->getMessage()}", [
                'exception' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }



    
    /**
     * Auto-submit exam due to security violation
     */
    private function autoSubmitViolatedExam($examSession, $user, $subjectId)
    {
        $subject = Subject::findOrFail($subjectId);
        $questions = Question::where('subject_id', $subjectId)->get()->keyBy('id');
        $submittedAnswers = $examSession->answers ?? [];
        $score = 0;
        $totalQuestions = $questions->count();
        $userAnswerRecords = [];

        // Calculate score from current answers
        if (is_array($submittedAnswers)) {
            foreach ($submittedAnswers as $questionId => $selectedLetter) {
                $question = $questions->get((int) $questionId);
                if ($question) {
                    $isCorrect = (strtoupper(trim($selectedLetter)) === strtoupper(trim($question->correct_answer)));
                    if ($isCorrect) {
                        $score++;
                    }
                    $userAnswerRecords[] = [
                        'user_id' => $user->id,
                        'question_id' => $question->id,
                        'selected_option_letter' => $selectedLetter,
                        'is_correct' => $isCorrect,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }
        
        // Insert user answers
        if (!empty($userAnswerRecords)) {
            UserAnswer::insert($userAnswerRecords);
        }

        // Calculate time taken
        $timeTakenSeconds = Carbon::now()->diffInSeconds($examSession->started_at);

        // Create user score record
        UserScore::create([
            'user_id' => $user->id,
            'subject_id' => $subjectId,
            'score' => $score,
            'total_questions' => $totalQuestions,
            'time_taken_seconds' => $timeTakenSeconds,
            'submission_time' => now(),
        ]);

        Log::info("Exam auto-submitted due to security violations for user {$user->id}, subject {$subjectId}, score: {$score}/{$totalQuestions}");
    }

    /**
     * Save current progress before forced logout
     */
    private function saveCurrentProgress($examSession)
    {
        try {
            // This would be called from JavaScript before the violation is reported
            // The progress should already be saved, but this is a safety measure
            $examSession->updateActivity();
        } catch (\Exception $e) {
            Log::error('Failed to save progress during security violation: ' . $e->getMessage());
        }
    }

    // Server-side progress saving (optional, more robust)
    // public function saveProgress(Request $request, Subject $subject)
    // {
    //     $user = Auth::user();
    //     ExamProgress::updateOrCreate(
    //         ['user_id' => $user->id, 'subject_id' => $subject->id, 'is_completed' => false],
    //         [
    //             'answers_json' => $request->input('answers'),
    //             'current_question_idx' => $request->input('currentQuestionIdx'),
    //             'original_start_time' => $request->input('originalStartTime'), // Ensure this is the very first start
    //             'last_activity_at' => now(),
    //         ]
    //     );
    //     return response()->json(['status' => 'progress_saved']);
    // }
}
