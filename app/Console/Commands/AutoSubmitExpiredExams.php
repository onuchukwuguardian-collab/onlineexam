<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExamSession;
use App\Models\UserScore;
use App\Models\UserAnswer;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoSubmitExpiredExams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exams:auto-submit-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically submit expired exam sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for expired exam sessions...');

        $expiredSessions = ExamSession::where('is_active', true)
            ->where('expires_at', '<', Carbon::now())
            ->with(['user', 'subject'])
            ->get();

        if ($expiredSessions->isEmpty()) {
            $this->info('No expired exam sessions found.');
            return;
        }

        $this->info("Found {$expiredSessions->count()} expired exam sessions.");

        foreach ($expiredSessions as $session) {
            try {
                $this->autoSubmitExpiredExam($session);
                $this->info("Auto-submitted exam for user {$session->user->name} in subject {$session->subject->name}");
            } catch (\Exception $e) {
                $this->error("Failed to auto-submit exam for session {$session->id}: " . $e->getMessage());
            }
        }

        $this->info('Expired exam processing completed.');
    }

    private function autoSubmitExpiredExam(ExamSession $examSession)
    {
        $user = $examSession->user;
        $subject = $examSession->subject;
        
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
            throw $e;
        }
    }
}
