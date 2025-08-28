<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\Question;
use App\Models\ExamSession;
use App\Http\Controllers\ExamController;
use Illuminate\Http\Request;

echo "=== EXAM ACCESS SIMULATION TEST ===\n\n";

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

echo "Simulating exam access for:\n";
echo "Student: {$student->name} (ID: {$student->id})\n";
echo "Subject: {$subject->name} (ID: {$subject->id})\n";
echo "Class ID: {$student->class_id}\n\n";

// Check if student can access this subject
if ($subject->class_id !== $student->class_id) {
    echo "❌ Student cannot access this subject (class mismatch)\n";
    exit;
}

echo "✅ Class access check passed\n";

// Check if there are questions for this subject
$questions = Question::with('options')->where('subject_id', $subject->id)->get();
echo "Questions found: {$questions->count()}\n";

if ($questions->isEmpty()) {
    echo "❌ No questions found for this subject\n";
    exit;
}

echo "✅ Questions found\n";

// Check if questions have options
$questionsWithOptions = 0;
foreach ($questions as $question) {
    if ($question->options->count() > 0) {
        $questionsWithOptions++;
    }
}

echo "Questions with options: {$questionsWithOptions}/{$questions->count()}\n";

if ($questionsWithOptions === 0) {
    echo "❌ No questions have options!\n";
    exit;
}

echo "✅ Questions have options\n";

// Simulate the exam controller logic
try {
    // Check for existing scores
    $existingScore = \App\Models\UserScore::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->first();
    
    if ($existingScore) {
        echo "⚠️  Student already has a score for this subject\n";
    } else {
        echo "✅ No existing score found\n";
    }
    
    // Check for existing sessions
    $existingSession = ExamSession::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->where('is_active', true)
        ->first();
    
    if ($existingSession) {
        echo "⚠️  Student has an active exam session\n";
    } else {
        echo "✅ No active exam session found\n";
    }
    
    // Prepare questions for JavaScript (same as controller)
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
    
    echo "✅ Questions formatted for frontend successfully\n";
    echo "Questions list count: " . $questionsList->count() . "\n";
    
    // Check first question structure
    if ($questionsList->count() > 0) {
        $firstQuestion = $questionsList->first();
        echo "First question structure:\n";
        echo "  - ID: " . $firstQuestion['id'] . "\n";
        echo "  - Text: " . substr($firstQuestion['text'], 0, 50) . "...\n";
        echo "  - Options: " . count($firstQuestion['options']) . "\n";
        
        if (count($firstQuestion['options']) > 0) {
            echo "  - First option: " . $firstQuestion['options'][0]['letter'] . ") " . substr($firstQuestion['options'][0]['text'], 0, 30) . "...\n";
        }
    }
    
    echo "\n✅ Exam access simulation successful!\n";
    
} catch (\Exception $e) {
    echo "❌ Error during simulation: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";