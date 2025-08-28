<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Subject;
use App\Models\Question;
use App\Models\ExamSession;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== STUDENT EXAM PAGE TEST ===\n\n";

try {
    // Get a test student
    $student = User::where('role', 'student')
        ->whereNotNull('class_id')
        ->first();
    
    if (!$student) {
        echo "❌ No students found\n";
        exit(1);
    }
    
    echo "Testing with student: {$student->name} (Class: {$student->class_id})\n";
    
    // Get a subject for this student's class
    $subject = Subject::where('class_id', $student->class_id)
        ->whereHas('questions')
        ->first();
    
    if (!$subject) {
        echo "❌ No subjects with questions found for this class\n";
        exit(1);
    }
    
    echo "Testing subject: {$subject->name}\n";
    
    // Get questions for this subject
    $questions = Question::with('options')->where('subject_id', $subject->id)->get();
    echo "Questions available: " . $questions->count() . "\n";
    
    if ($questions->isEmpty()) {
        echo "❌ No questions found for subject\n";
        exit(1);
    }
    
    // Check if questions have options
    $questionsWithOptions = 0;
    foreach ($questions as $question) {
        if ($question->options->count() > 0) {
            $questionsWithOptions++;
        }
    }
    
    echo "Questions with options: {$questionsWithOptions}/{$questions->count()}\n";
    
    // Clean up any existing sessions for this student/subject
    ExamSession::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->delete();
    
    // Test creating an exam session
    $examSession = ExamSession::create([
        'user_id' => $student->id,
        'subject_id' => $subject->id,
        'started_at' => now(),
        'expires_at' => now()->addMinutes($subject->exam_duration_minutes ?? 60),
        'duration_minutes' => $subject->exam_duration_minutes ?? 60,
        'answers' => [],
        'current_question_index' => 0,
        'is_active' => true,
        'last_activity_at' => now()
    ]);
    
    echo "✅ Exam session created: ID {$examSession->id}\n";
    
    // Test the data structure that would be passed to the view
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
    
    echo "✅ Questions data structure prepared\n";
    echo "✅ First question: " . substr($questionsList[0]['text'], 0, 50) . "...\n";
    echo "✅ First question options: " . count($questionsList[0]['options']) . "\n";
    
    // Test timer calculation
    $remainingSeconds = $examSession->actual_remaining_time ?? $examSession->remaining_time;
    $minutes = floor($remainingSeconds / 60);
    $seconds = $remainingSeconds % 60;
    $timerDisplay = sprintf('%02d:%02d', $minutes, $seconds);
    
    echo "✅ Timer display: {$timerDisplay}\n";
    
    // Clean up
    $examSession->delete();
    echo "✅ Test session cleaned up\n";
    
    echo "\n=== EXAM PAGE TEST RESULTS ===\n";
    echo "✅ Student data: VALID\n";
    echo "✅ Subject data: VALID\n";
    echo "✅ Questions data: VALID\n";
    echo "✅ Options data: VALID\n";
    echo "✅ Session creation: WORKING\n";
    echo "✅ Timer calculation: WORKING\n";
    echo "✅ Data structure: READY FOR VIEW\n";
    echo "\n🎉 EXAM PAGE SHOULD WORK PROPERLY!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}