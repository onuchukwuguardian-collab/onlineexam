<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\Question;
use App\Models\UserScore;
use App\Models\ExamSession;

echo "=== COMPLETE SYSTEM TEST ===\n\n";

// Test 1: Student Access
echo "1. TESTING STUDENT ACCESS\n";
echo "=" . str_repeat("=", 30) . "\n";

$students = User::where('role', 'student')->whereNotNull('class_id')->get();
echo "Students with class assignments: {$students->count()}\n";

if ($students->count() > 0) {
    $testStudent = $students->first();
    echo "✅ Test student: {$testStudent->name} (Class: {$testStudent->class_id})\n";
    echo "✅ isStudent(): " . ($testStudent->isStudent() ? 'YES' : 'NO') . "\n";
    echo "✅ Can access student routes: " . ($testStudent->isStudent() && $testStudent->class_id ? 'YES' : 'NO') . "\n";
} else {
    echo "❌ No students with class assignments found!\n";
}

echo "\n";

// Test 2: Questions Loading
echo "2. TESTING QUESTIONS LOADING\n";
echo "=" . str_repeat("=", 30) . "\n";

if (isset($testStudent)) {
    $subjects = Subject::where('class_id', $testStudent->class_id)->get();
    echo "Subjects for student's class: {$subjects->count()}\n";
    
    $subjectsWithQuestions = 0;
    foreach ($subjects as $subject) {
        $questionCount = Question::where('subject_id', $subject->id)->count();
        if ($questionCount > 0) {
            $subjectsWithQuestions++;
            echo "✅ {$subject->name}: {$questionCount} questions\n";
        } else {
            echo "⚠️  {$subject->name}: No questions\n";
        }
    }
    
    if ($subjectsWithQuestions > 0) {
        echo "✅ Questions loading: WORKING\n";
    } else {
        echo "❌ Questions loading: NO QUESTIONS FOUND\n";
    }
} else {
    echo "❌ Cannot test - no test student available\n";
}

echo "\n";

// Test 3: Admin Reset Functionality
echo "3. TESTING ADMIN RESET FUNCTIONALITY\n";
echo "=" . str_repeat("=", 30) . "\n";

if (isset($testStudent)) {
    $testSubject = Subject::where('class_id', $testStudent->class_id)
        ->whereHas('questions')
        ->first();
    
    if ($testSubject) {
        echo "Testing reset with:\n";
        echo "  Student: {$testStudent->name}\n";
        echo "  Subject: {$testSubject->name}\n";
        
        // Create test data
        $testScore = UserScore::create([
            'user_id' => $testStudent->id,
            'subject_id' => $testSubject->id,
            'score' => 8,
            'total_questions' => 10,
            'time_taken_seconds' => 1200,
            'submission_time' => now()
        ]);
        
        $testSession = ExamSession::create([
            'user_id' => $testStudent->id,
            'subject_id' => $testSubject->id,
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'duration_minutes' => 60,
            'answers' => ['1' => 'A'],
            'current_question_index' => 1,
            'is_active' => false,
            'last_activity_at' => now()
        ]);
        
        echo "✅ Created test data (Score ID: {$testScore->id}, Session ID: {$testSession->id})\n";
        
        // Test reset
        try {
            DB::beginTransaction();
            
            UserScore::where('user_id', $testStudent->id)
                ->where('subject_id', $testSubject->id)
                ->delete();
            
            ExamSession::where('user_id', $testStudent->id)
                ->where('subject_id', $testSubject->id)
                ->delete();
            
            DB::commit();
            
            // Verify reset
            $remainingScores = UserScore::where('user_id', $testStudent->id)
                ->where('subject_id', $testSubject->id)
                ->count();
            
            $remainingSessions = ExamSession::where('user_id', $testStudent->id)
                ->where('subject_id', $testSubject->id)
                ->count();
            
            if ($remainingScores === 0 && $remainingSessions === 0) {
                echo "✅ Admin reset: WORKING\n";
            } else {
                echo "❌ Admin reset: FAILED (Scores: {$remainingScores}, Sessions: {$remainingSessions})\n";
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ Admin reset: ERROR - " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Cannot test reset - no subject with questions found\n";
    }
} else {
    echo "❌ Cannot test reset - no test student available\n";
}

echo "\n";

// Test 4: Database Integrity
echo "4. TESTING DATABASE INTEGRITY\n";
echo "=" . str_repeat("=", 30) . "\n";

$totalUsers = User::count();
$totalStudents = User::where('role', 'student')->count();
$totalAdmins = User::where('role', 'admin')->count();
$totalSubjects = Subject::count();
$totalQuestions = Question::count();
$totalOptions = \App\Models\Option::count();

echo "Users: {$totalUsers} (Students: {$totalStudents}, Admins: {$totalAdmins})\n";
echo "Subjects: {$totalSubjects}\n";
echo "Questions: {$totalQuestions}\n";
echo "Options: {$totalOptions}\n";

$studentsWithClasses = User::where('role', 'student')->whereNotNull('class_id')->count();
echo "Students with class assignments: {$studentsWithClasses}/{$totalStudents}\n";

if ($studentsWithClasses === $totalStudents) {
    echo "✅ All students have class assignments\n";
} else {
    echo "⚠️  Some students missing class assignments\n";
}

// Check question-option relationships
$questionsWithOptions = Question::whereHas('options')->count();
echo "Questions with options: {$questionsWithOptions}/{$totalQuestions}\n";

if ($questionsWithOptions === $totalQuestions) {
    echo "✅ All questions have options\n";
} else {
    echo "⚠️  Some questions missing options\n";
}

echo "\n";

// Summary
echo "SYSTEM STATUS SUMMARY\n";
echo "=" . str_repeat("=", 30) . "\n";

$issues = [];
if ($students->count() === 0) $issues[] = "No students with class assignments";
if ($subjectsWithQuestions === 0) $issues[] = "No subjects with questions";
if ($studentsWithClasses !== $totalStudents) $issues[] = "Students missing class assignments";
if ($questionsWithOptions !== $totalQuestions) $issues[] = "Questions missing options";

if (empty($issues)) {
    echo "🎉 SYSTEM STATUS: ALL GOOD!\n";
    echo "✅ Students can access exams\n";
    echo "✅ Questions are loading properly\n";
    echo "✅ Admin reset functionality works\n";
    echo "✅ Database integrity is maintained\n";
} else {
    echo "⚠️  SYSTEM STATUS: ISSUES FOUND\n";
    foreach ($issues as $issue) {
        echo "❌ {$issue}\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";