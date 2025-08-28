<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\UserScore;
use App\Models\UserAnswer;
use App\Models\ExamSession;

echo "=== ADMIN RESET FUNCTIONALITY TEST ===\n\n";

// Find a student with class_id and a subject with questions
$student = User::where('role', 'student')
    ->whereNotNull('class_id')
    ->first();

if (!$student) {
    echo "❌ No students found!\n";
    exit;
}

$subject = Subject::where('class_id', $student->class_id)
    ->whereHas('questions')
    ->first();

if (!$subject) {
    echo "❌ No subjects with questions found for this student!\n";
    exit;
}

echo "Testing with:\n";
echo "Student: {$student->name} (ID: {$student->id})\n";
echo "Subject: {$subject->name} (ID: {$subject->id})\n\n";

// Create some test data to reset
echo "Creating test data...\n";

// Create a test score
$testScore = UserScore::create([
    'user_id' => $student->id,
    'subject_id' => $subject->id,
    'score' => 5,
    'total_questions' => 10,
    'time_taken_seconds' => 1800,
    'submission_time' => now()
]);
echo "✅ Created test score (ID: {$testScore->id})\n";

// Create a test exam session
$testSession = ExamSession::create([
    'user_id' => $student->id,
    'subject_id' => $subject->id,
    'started_at' => now(),
    'expires_at' => now()->addHour(),
    'duration_minutes' => 60,
    'answers' => ['1' => 'A', '2' => 'B'],
    'current_question_index' => 2,
    'is_active' => false,
    'last_activity_at' => now()
]);
echo "✅ Created test exam session (ID: {$testSession->id})\n";

// Check data exists
echo "\nBefore reset:\n";
echo "Scores: " . UserScore::where('user_id', $student->id)->where('subject_id', $subject->id)->count() . "\n";
echo "Sessions: " . ExamSession::where('user_id', $student->id)->where('subject_id', $subject->id)->count() . "\n";

// Test the reset functionality
echo "\nTesting reset functionality...\n";

try {
    // Simulate the reset process
    DB::beginTransaction();
    
    // Delete user score for this subject
    UserScore::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->delete();
    
    // Delete exam sessions for this subject
    ExamSession::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->delete();
    
    DB::commit();
    
    echo "✅ Reset completed successfully\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ Reset failed: " . $e->getMessage() . "\n";
}

// Check data after reset
echo "\nAfter reset:\n";
echo "Scores: " . UserScore::where('user_id', $student->id)->where('subject_id', $subject->id)->count() . "\n";
echo "Sessions: " . ExamSession::where('user_id', $student->id)->where('subject_id', $subject->id)->count() . "\n";

echo "\n=== TEST COMPLETE ===\n";