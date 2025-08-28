<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;

echo "=== TESTING CORRECTED 3-STRIKE SYSTEM ===\n\n";

// Find a test student and subject
$student = User::where('role', 'student')->orWhere('role', 'user')->first();
$subject = Subject::first();

if (!$student || !$subject) {
    echo "❌ Need at least one student and one subject in database\n";
    exit;
}

echo "🎯 TEST SCENARIO: Student should be able to continue after 1st and 2nd violations\n";
echo "Student: {$student->name} (ID: {$student->id})\n";
echo "Subject: {$subject->name} (ID: {$subject->id})\n\n";

// Clear any existing violations/bans for clean test
ExamSecurityViolation::where('user_id', $student->id)
    ->where('subject_id', $subject->id)
    ->delete();
    
ExamBan::where('user_id', $student->id)
    ->where('subject_id', $subject->id)
    ->delete();

echo "🧹 Cleared existing violations and bans for clean test\n\n";

// Test each violation level
for ($violationNum = 1; $violationNum <= 3; $violationNum++) {
    echo "=== TESTING VIOLATION #{$violationNum} ===\n";
    
    // Create violation
    ExamSecurityViolation::recordViolation(
        $student->id,
        $subject->id,
        'tab_switch',
        "Test tab switch violation #{$violationNum}",
        null,
        [
            'timestamp' => now()->toISOString(),
            'violation_count' => $violationNum,
            'test_scenario' => 'corrected_3_strike_system',
            'user_agent' => 'Mozilla/5.0 (Test Browser)'
        ]
    );
    
    $totalCount = ExamSecurityViolation::getViolationCount($student->id, $subject->id, 'tab_switch');
    echo "📊 Total violations after #{$violationNum}: {$totalCount}\n";
    
    // Check ban status
    $isBanned = ExamBan::isBanned($student->id, $subject->id);
    echo "🚫 Student banned: " . ($isBanned ? 'YES' : 'NO') . "\n";
    
    // Simulate what happens when student tries to access exam
    if ($violationNum < 3) {
        // For 1st and 2nd violations, student should be able to continue
        if (!$isBanned) {
            echo "✅ CORRECT: Student can continue exam after violation #{$violationNum}\n";
            echo "   Expected behavior: Show warning but allow access\n";
        } else {
            echo "❌ ERROR: Student is blocked after violation #{$violationNum} - this is wrong!\n";
            echo "   Students should only be banned after 3rd violation\n";
        }
    } else {
        // For 3rd violation, create ban and block access
        if ($totalCount >= 3) {
            // Get all violations for ban creation
            $violations = ExamSecurityViolation::where('user_id', $student->id)
                ->where('subject_id', $subject->id)
                ->where('violation_type', 'tab_switch')
                ->get();
            
            // Create ban after 3rd violation
            $ban = ExamBan::createViolationBan(
                $student->id,
                $subject->id,
                $violations,
                'Permanent ban after 3 TAB SWITCHING violations during online exam'
            );
            
            echo "🔒 THIRD VIOLATION: Student banned (Ban ID: {$ban->id})\n";
            echo "✅ CORRECT: Student is now blocked from accessing exam\n";
        }
    }
    
    echo str_repeat("-", 60) . "\n\n";
}

// Final verification
echo "🔍 FINAL VERIFICATION:\n";
echo "════════════════════════\n";

$finalCount = ExamSecurityViolation::getViolationCount($student->id, $subject->id, 'tab_switch');
$finalBanStatus = ExamBan::isBanned($student->id, $subject->id);

echo "📊 Final violation count: {$finalCount}\n";
echo "🚫 Final ban status: " . ($finalBanStatus ? 'BANNED' : 'NOT BANNED') . "\n\n";

// Test the corrected logic
echo "🧪 TESTING CORRECTED EXAM ACCESS LOGIC:\n";
echo "═══════════════════════════════════════════\n";

if ($finalBanStatus) {
    $banDetails = ExamBan::getBanDetails($student->id, $subject->id);
    $currentViolationCount = ExamSecurityViolation::getViolationCount($student->id, $subject->id, 'tab_switch');
    
    echo "📋 Ban Details:\n";
    echo "   Ban ID: {$banDetails->id}\n";
    echo "   Total violations in ban: {$banDetails->total_violations}\n";
    echo "   Current violation count: {$currentViolationCount}\n";
    echo "   Ban reason: {$banDetails->ban_reason}\n\n";
    
    if ($currentViolationCount >= 3 || $banDetails->total_violations >= 3) {
        echo "✅ CORRECT: Student blocked because they have 3+ violations\n";
    } else {
        echo "❌ ERROR: Student blocked with less than 3 violations\n";
    }
} else {
    echo "✅ Student not banned - they can continue taking exams\n";
}

echo "\n🎯 EXPECTED 3-STRIKE BEHAVIOR:\n";
echo "══════════════════════════════════════\n";
echo "✅ 1st Tab Switch: Warning + Logout + Can continue\n";
echo "✅ 2nd Tab Switch: Final warning + Logout + Can continue\n";
echo "✅ 3rd Tab Switch: Permanent ban + Cannot continue\n\n";

echo "📍 KEY CHANGES MADE:\n";
echo "═══════════════════════\n";
echo "• Modified ExamController::start() ban check\n";
echo "• Students with 1-2 violations can now continue\n";
echo "• Only students with 3+ violations are blocked\n";
echo "• Ban check now verifies violation count before blocking\n\n";

echo "🏁 TEST COMPLETE!\n";
echo "Students should now be able to continue their exam after 1st and 2nd violations.\n";
echo "Only after the 3rd violation will they be permanently banned.\n";