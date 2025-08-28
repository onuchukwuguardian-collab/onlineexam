<?php
/**
 * Test script to check if questions are loading properly
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Question;
use App\Models\Option;
use App\Models\Subject;
use App\Models\User;

echo "=== QUESTIONS LOADING TEST ===\n\n";

// Test 1: Check if questions exist
$questionsCount = Question::count();
echo "Total questions in database: {$questionsCount}\n";

// Test 2: Check if options exist
$optionsCount = Option::count();
echo "Total options in database: {$optionsCount}\n";

// Test 3: Check subjects
$subjectsCount = Subject::count();
echo "Total subjects in database: {$subjectsCount}\n\n";

// Test 4: Check a specific question with options
$question = Question::with('options')->first();
if ($question) {
    echo "First question found:\n";
    echo "ID: {$question->id}\n";
    echo "Subject ID: {$question->subject_id}\n";
    echo "Text: " . substr($question->question_text, 0, 100) . "...\n";
    echo "Options count: " . $question->options->count() . "\n";
    
    foreach ($question->options as $option) {
        echo "  - {$option->option_letter}: " . substr($option->option_text, 0, 50) . "...\n";
    }
} else {
    echo "❌ No questions found!\n";
}

echo "\n";

// Test 5: Check if a subject has questions
$subject = Subject::first();
if ($subject) {
    $subjectQuestions = Question::where('subject_id', $subject->id)->with('options')->get();
    echo "Subject '{$subject->name}' has {$subjectQuestions->count()} questions\n";
    
    if ($subjectQuestions->count() > 0) {
        echo "✅ Questions are properly linked to subjects\n";
    } else {
        echo "❌ No questions found for this subject\n";
    }
} else {
    echo "❌ No subjects found!\n";
}

echo "\n";

// Test 6: Check student access
$student = User::where('role', 'student')->first();
if ($student) {
    echo "Testing student access:\n";
    echo "Student: {$student->name}\n";
    echo "Class ID: {$student->class_id}\n";
    
    $studentSubjects = Subject::where('class_id', $student->class_id)->get();
    echo "Student has access to {$studentSubjects->count()} subjects\n";
    
    foreach ($studentSubjects as $subj) {
        $questionsForSubject = Question::where('subject_id', $subj->id)->count();
        echo "  - {$subj->name}: {$questionsForSubject} questions\n";
    }
} else {
    echo "❌ No students found!\n";
}

echo "\n=== TEST COMPLETE ===\n";