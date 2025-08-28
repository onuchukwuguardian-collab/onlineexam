<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\Question;

echo "=== STUDENT EXAM ACCESS TEST ===\n\n";

// Find a student with a class_id
$student = User::where('role', 'student')
    ->whereNotNull('class_id')
    ->first();

if (!$student) {
    echo "❌ No students with class_id found!\n";
    exit;
}

echo "Testing with student: {$student->name}\n";
echo "Class ID: {$student->class_id}\n\n";

// Check subjects for this student's class
$subjects = Subject::where('class_id', $student->class_id)->get();
echo "Subjects available to this student: {$subjects->count()}\n";

foreach ($subjects as $subject) {
    echo "\nSubject: {$subject->name} (ID: {$subject->id})\n";
    
    // Check questions for this subject
    $questions = Question::with('options')
        ->where('subject_id', $subject->id)
        ->get();
    
    echo "  Questions: {$questions->count()}\n";
    
    if ($questions->count() > 0) {
        echo "  ✅ Questions found for this subject\n";
        
        // Test the exact same query used in ExamController
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
        
        echo "  ✅ Questions formatted successfully for frontend\n";
        echo "  First question: " . substr($questionsList[0]['text'], 0, 50) . "...\n";
        echo "  Options count: " . count($questionsList[0]['options']) . "\n";
    } else {
        echo "  ❌ No questions found for this subject\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";