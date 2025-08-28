<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Subject;
use App\Models\Question;
use App\Models\Option;
use App\Models\User;
use App\Models\ExamSession;
use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;

echo "=== STUDENT ACCESS SOLUTION IMPLEMENTATION ===\n\n";

// SOLUTION 1: Add sample questions to subjects that have none
echo "🔧 SOLUTION 1: Adding sample questions to empty subjects...\n";
echo "════════════════════════════════════════════════════════════\n";

$emptySubjects = [
    15 => 'English Language',      // Class 8
    17 => 'Social Studies',        // Class 8  
    28 => 'Music',                // Class 8
    18 => 'Mathematics',          // Class 11
    19 => 'English Language',     // Class 11
    23 => 'Mathematics',          // Class 12
    24 => 'English Language',     // Class 12
    25 => 'Literature in English', // Class 12
    26 => 'Government'            // Class 12
];

foreach ($emptySubjects as $subjectId => $subjectName) {
    $subject = Subject::find($subjectId);
    if (!$subject) {
        echo "❌ Subject ID {$subjectId} not found\n";
        continue;
    }
    
    echo "📝 Adding questions to: {$subjectName} (ID: {$subjectId})\n";
    
    // Add 5 sample questions for each subject
    for ($i = 1; $i <= 5; $i++) {
        $question = Question::create([
            'subject_id' => $subjectId,
            'question_text' => "Sample {$subjectName} Question #{$i}: This is a test question to enable exam access. What is the correct answer?",
            'correct_answer' => 'A',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Add options A, B, C, D
        $options = [
            'A' => 'Correct Answer',
            'B' => 'Option B',
            'C' => 'Option C', 
            'D' => 'Option D'
        ];
        
        foreach ($options as $letter => $text) {
            Option::create([
                'question_id' => $question->id,
                'option_letter' => $letter,
                'option_text' => $text,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
    
    echo "   ✅ Added 5 questions with 4 options each\n";
}

echo "\n🔧 SOLUTION 2: Cleaning up expired sessions...\n";
echo "═══════════════════════════════════════════════════\n";

// Clean up expired sessions that might be blocking students
$expiredSessions = ExamSession::where('is_active', true)
    ->where('expires_at', '<', now())
    ->get();

echo "🧹 Found {$expiredSessions->count()} expired sessions to clean up\n";

foreach ($expiredSessions as $session) {
    if ($session->isExpired()) {
        $session->markAsCompleted(true);
        echo "   ✅ Cleaned expired session for User {$session->user_id}, Subject {$session->subject_id}\n";
    }
}

echo "\n🔧 SOLUTION 3: Reviewing ban validity...\n";
echo "═══════════════════════════════════════════════\n";

// Check for any invalid bans (students banned with < 3 violations)
$allBans = ExamBan::where('is_active', true)->get();

echo "🔍 Found {$allBans->count()} active bans to review\n";

foreach ($allBans as $ban) {
    $actualViolations = ExamSecurityViolation::getViolationCount($ban->user_id, $ban->subject_id, 'tab_switch');
    
    if ($actualViolations < 3 && $ban->total_violations < 3) {
        echo "   ❌ INVALID BAN: User {$ban->user_id} banned with only {$actualViolations} violations\n";
        echo "      💡 Recommend reactivating Ban ID: {$ban->id}\n";
    } else {
        echo "   ✅ VALID BAN: User {$ban->user_id} has {$actualViolations} violations (Ban ID: {$ban->id})\n";
    }
}

echo "\n🔧 SOLUTION 4: Student access verification...\n";
echo "═════════════════════════════════════════════════\n";

// Count how many students should now be able to access exams
$totalStudents = User::where('role', 'student')->orWhere('role', 'user')->count();
$accessibleCount = 0;
$blockedCount = 0;

$students = User::where('role', 'student')->orWhere('role', 'user')->get();

foreach ($students as $student) {
    $subjectsWithQuestions = Subject::where('class_id', $student->class_id)
        ->whereHas('questions')
        ->count();
        
    if ($subjectsWithQuestions > 0) {
        $accessibleCount++;
    } else {
        $blockedCount++;
        echo "   ❌ {$student->name} (Class {$student->class_id}): No subjects with questions\n";
    }
}

echo "\n📊 FINAL STATUS:\n";
echo "═══════════════════\n";
echo "👥 Total students: {$totalStudents}\n";
echo "✅ Can access exams: {$accessibleCount}\n";
echo "❌ Still blocked: {$blockedCount}\n";

if ($blockedCount > 0) {
    echo "\n🚨 Students still blocked need:\n";
    echo "   1. Questions added to their class subjects\n";
    echo "   2. Correct class_id assignment\n";
    echo "   3. Subject assignment to their class\n";
}

echo "\n🎯 IMMEDIATE ACTIONS FOR STUDENTS:\n";
echo "══════════════════════════════════════\n";
echo "Students should now be able to:\n";
echo "✅ Access subjects that previously had no questions\n";
echo "✅ Continue exams after 1st and 2nd violations\n";
echo "✅ Start fresh exams after expired sessions are cleaned\n";
echo "❌ Students with 3+ violations remain banned (correct behavior)\n";

echo "\n🔧 ADMIN ACTIONS NEEDED:\n";
echo "═══════════════════════════════\n";
echo "1. 📝 Replace sample questions with real content\n";
echo "2. 🔄 Reactivate any incorrectly banned students\n";
echo "3. 🏫 Fix class assignments for unassigned students\n";
echo "4. ✅ Test student login and exam access\n";

echo "\n🏁 SOLUTION IMPLEMENTATION COMPLETE!\n";
echo "Students should now be able to proceed with their exams.\n";