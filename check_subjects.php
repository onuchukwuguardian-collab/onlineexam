<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Subject;
use App\Models\Question;

echo "=== SUBJECTS AND QUESTION COUNTS ===\n\n";

$subjects = Subject::orderBy('class_id')->get();

foreach ($subjects as $subject) {
    $questionCount = Question::where('subject_id', $subject->id)->count();
    $status = $questionCount > 0 ? "✅" : "❌";
    
    echo "{$status} {$subject->name} (ID: {$subject->id}, Class: {$subject->class_id}): {$questionCount} questions\n";
}

echo "\n=== SUMMARY ===\n";
$totalSubjects = $subjects->count();
$subjectsWithQuestions = $subjects->filter(function($subject) {
    return Question::where('subject_id', $subject->id)->count() > 0;
})->count();
$subjectsWithoutQuestions = $totalSubjects - $subjectsWithQuestions;

echo "Total subjects: {$totalSubjects}\n";
echo "✅ With questions: {$subjectsWithQuestions}\n";
echo "❌ Without questions: {$subjectsWithoutQuestions}\n";

if ($subjectsWithoutQuestions > 0) {
    echo "\n🚨 URGENT: {$subjectsWithoutQuestions} subjects need questions added!\n";
}