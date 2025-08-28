<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use App\Models\ExamSession;
use App\Models\UserScore;
use App\Models\Question;

echo "=== DEBUGGING STUDENT ACCESS ISSUES ===\n\n";

// Find students who might be stuck
$students = User::where('role', 'student')
    ->orWhere('role', 'user')
    ->get();

if ($students->isEmpty()) {
    echo "❌ No students found in the system!\n";
    exit;
}

echo "🔍 Found {$students->count()} students in the system\n\n";

foreach ($students as $student) {
    echo "👤 STUDENT: {$student->name} (ID: {$student->id})\n";
    echo "   📧 Email: {$student->email}\n";
    echo "   🆔 Registration: " . ($student->registration_number ?? 'Not set') . "\n";
    echo "   🏫 Class ID: " . ($student->class_id ?? 'Not set') . "\n";
    
    // Check if student has any subjects available
    $availableSubjects = Subject::where('class_id', $student->class_id)->get();
    echo "   📚 Available subjects: {$availableSubjects->count()}\n";
    
    if ($availableSubjects->isEmpty()) {
        echo "   ❌ PROBLEM: No subjects available for this student's class!\n";
        echo "   💡 SOLUTION: Assign subjects to class {$student->class_id} or set correct class_id for student\n\n";
        continue;
    }
    
    foreach ($availableSubjects as $subject) {
        echo "\n   📖 SUBJECT: {$subject->name} (ID: {$subject->id})\n";
        
        // Check questions
        $questionCount = Question::where('subject_id', $subject->id)->count();
        echo "      ❓ Questions: {$questionCount}\n";
        
        if ($questionCount == 0) {
            echo "      ❌ PROBLEM: No questions in this subject!\n";
            echo "      💡 SOLUTION: Add questions to subject {$subject->name}\n";
            continue;
        }
        
        // Check violations
        $violationCount = ExamSecurityViolation::getViolationCount($student->id, $subject->id, 'tab_switch');
        echo "      ⚠️ Violations: {$violationCount}\n";
        
        // Check ban status
        $isBanned = ExamBan::isBanned($student->id, $subject->id);
        echo "      🚫 Banned: " . ($isBanned ? 'YES' : 'NO') . "\n";
        
        if ($isBanned) {
            $banDetails = ExamBan::getBanDetails($student->id, $subject->id);
            echo "      📋 Ban ID: {$banDetails->id}\n";
            echo "      📅 Banned at: {$banDetails->banned_at}\n";
            echo "      🔢 Total violations in ban: {$banDetails->total_violations}\n";
            echo "      📝 Ban reason: {$banDetails->ban_reason}\n";
            
            // Check if ban should actually block access
            if ($violationCount >= 3 || $banDetails->total_violations >= 3) {
                echo "      ✅ Ban is VALID - student should be blocked\n";
            } else {
                echo "      ❌ Ban is INVALID - student should NOT be blocked! (Only {$violationCount} violations)\n";
                echo "      💡 REACTIVATE or DELETE this incorrect ban\n";
            }
        }
        
        // Check existing exam session
        $existingSession = ExamSession::where('user_id', $student->id)
            ->where('subject_id', $subject->id)
            ->where('is_active', true)
            ->first();
            
        if ($existingSession) {
            echo "      🎯 Active session: YES (ID: {$existingSession->id})\n";
            echo "         Started: {$existingSession->started_at}\n";
            echo "         Expires: {$existingSession->expires_at}\n";
            echo "         Remaining: {$existingSession->remaining_time} seconds\n";
            echo "         Expired: " . ($existingSession->isExpired() ? 'YES' : 'NO') . "\n";
            
            if ($existingSession->isExpired()) {
                echo "      ⚠️ Session is EXPIRED - needs cleanup\n";
            }
        } else {
            echo "      🎯 Active session: NO\n";
        }
        
        // Check existing score
        $existingScore = UserScore::where('user_id', $student->id)
            ->where('subject_id', $subject->id)
            ->first();
            
        if ($existingScore) {
            echo "      📊 Completed: YES (Score: {$existingScore->score}/{$existingScore->total_questions})\n";
            echo "         Submitted: {$existingScore->created_at}\n";
        } else {
            echo "      📊 Completed: NO\n";
        }
        
        // SIMULATE ACCESS ATTEMPT
        echo "      🧪 ACCESS TEST:\n";
        
        // Test 1: Class ID mismatch
        if ($subject->class_id !== $student->class_id) {
            echo "         ❌ BLOCKED: Class ID mismatch (Subject: {$subject->class_id}, Student: {$student->class_id})\n";
        }
        
        // Test 2: Ban check
        if ($isBanned && ($violationCount >= 3 || $banDetails->total_violations >= 3)) {
            echo "         ❌ BLOCKED: Permanently banned (3+ violations)\n";
        }
        
        // Test 3: Already completed
        if ($existingScore && !$existingSession) {
            echo "         ❌ BLOCKED: Already completed this exam\n";
        }
        
        // Test 4: No questions
        if ($questionCount == 0) {
            echo "         ❌ BLOCKED: No questions available\n";
        }
        
        // Test 5: Session expired
        if ($existingSession && $existingSession->isExpired() && !$existingScore) {
            echo "         ⚠️ EXPIRED: Session needs auto-submit\n";
        }
        
        // Overall assessment
        $canAccess = true;
        $blockReasons = [];
        
        if ($subject->class_id !== $student->class_id) {
            $canAccess = false;
            $blockReasons[] = "Class mismatch";
        }
        
        if ($isBanned && ($violationCount >= 3 || $banDetails->total_violations >= 3)) {
            $canAccess = false;
            $blockReasons[] = "Permanently banned";
        }
        
        if ($existingScore && !$existingSession) {
            $canAccess = false;
            $blockReasons[] = "Already completed";
        }
        
        if ($questionCount == 0) {
            $canAccess = false;
            $blockReasons[] = "No questions";
        }
        
        if ($canAccess) {
            echo "         ✅ SHOULD BE ABLE TO ACCESS\n";
        } else {
            echo "         ❌ BLOCKED BY: " . implode(", ", $blockReasons) . "\n";
        }
    }
    
    echo "\n" . str_repeat("=", 80) . "\n\n";
}

// Summary of common issues
echo "🔍 COMMON ISSUES THAT BLOCK STUDENT ACCESS:\n";
echo "═══════════════════════════════════════════════════\n";
echo "1. ❌ Class ID mismatch: Student class_id ≠ Subject class_id\n";
echo "2. ❌ No questions: Subject has 0 questions\n";
echo "3. ❌ Invalid bans: Students banned with < 3 violations\n";
echo "4. ❌ Already completed: Student has existing score\n";
echo "5. ❌ Expired sessions: Need cleanup/auto-submit\n\n";

echo "💡 SOLUTIONS:\n";
echo "═════════════════\n";
echo "1. Fix class assignments: Update student or subject class_id\n";
echo "2. Add questions to subjects\n";
echo "3. Reactivate incorrectly banned students\n";
echo "4. Reset completed exams if needed\n";
echo "5. Clean up expired sessions\n\n";

echo "🏁 DIAGNOSIS COMPLETE!\n";
echo "Check the output above to identify why specific students are stuck.\n";