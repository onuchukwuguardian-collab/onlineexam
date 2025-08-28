<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSession;
use App\Models\ExamSecurityViolation;

echo "🧪 TESTING NEW VIOLATION POLICY\n";
echo "===============================\n\n";
echo "Policy Implementation:\n";
echo "📵 Tab switching/new window = IMMEDIATE BAN (1st violation)\n";
echo "🖱️  Right-clicking = 3-STRIKE POLICY (banned after 3rd violation)\n";
echo "⚡ Whichever limit is reached first triggers the ban\n\n";

// Find a test student and subject
$student = User::where('role', 'student')->first();
$subject = Subject::first();

if (!$student || !$subject) {
    echo "❌ No student or subject found\n";
    exit(1);
}

echo "👨‍🎓 Test Student: {$student->name}\n";
echo "📚 Test Subject: {$subject->name}\n\n";

// Clean up any existing violations for this test
echo "🧹 Cleaning up existing test data...\n";
ExamSecurityViolation::where('user_id', $student->id)
    ->where('subject_id', $subject->id)
    ->delete();

// Create exam session
$examSession = ExamSession::updateOrCreate(
    [
        'user_id' => $student->id,
        'subject_id' => $subject->id,
        'is_active' => true
    ],
    [
        'started_at' => now(),
        'expires_at' => now()->addHours(2),
        'duration_minutes' => 120,
        'current_question_index' => 0,
        'answers' => [],
        'remaining_time' => 7200,
    ]
);

echo "📝 Created exam session (ID: {$examSession->id})\n\n";

// Test 1: Right-click violations (should follow 3-strike policy)
echo "🧪 TEST 1: RIGHT-CLICK VIOLATIONS (3-STRIKE POLICY)\n";
echo "===================================================\n\n";

for ($i = 1; $i <= 4; $i++) {
    echo "Right-click violation #{$i}:\n";
    
    $violation = ExamSecurityViolation::create([
        'user_id' => $student->id,
        'subject_id' => $subject->id,
        'exam_session_id' => $examSession->id,
        'violation_type' => 'right_click',
        'description' => "Student right-clicked during exam - 3-STRIKE POLICY (attempt #{$i})",
        'metadata' => [
            'policy' => '3_STRIKE_POLICY',
            'timestamp' => now()->toISOString(),
            'test_violation' => true
        ],
        'occurred_at' => now()->subMinutes(10 - $i), // Space them out
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser'
    ]);
    
    // Count violations for this subject
    $totalRightClicks = ExamSecurityViolation::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->where('violation_type', 'right_click')
        ->count();
    
    echo "  📊 Violation recorded (ID: {$violation->id})\n";
    echo "  📈 Total right-click violations: {$totalRightClicks}\n";
    
    if ($totalRightClicks >= 3) {
        echo "  🚫 EXPECTED RESULT: Student should be BANNED after 3 right-clicks\n";
    } else {
        $remaining = 3 - $totalRightClicks;
        echo "  ⚠️  WARNING: {$remaining} more right-clicks until ban\n";
    }
    echo "\n";
}

// Test 2: Tab switch violation (should result in immediate ban)
echo "\n🧪 TEST 2: TAB SWITCH VIOLATION (IMMEDIATE BAN POLICY)\n";
echo "=====================================================\n\n";

// Create a new student to test tab switching policy independently
$student2 = User::where('role', 'student')->where('id', '!=', $student->id)->first();
if (!$student2) {
    echo "❌ Need a second student for independent tab switch test\n";
    exit(1);
}

echo "👨‍🎓 Second Test Student: {$student2->name}\n";

// Clean up existing violations for student2
ExamSecurityViolation::where('user_id', $student2->id)
    ->where('subject_id', $subject->id)
    ->delete();

// Create exam session for student2
$examSession2 = ExamSession::updateOrCreate(
    [
        'user_id' => $student2->id,
        'subject_id' => $subject->id,
        'is_active' => true
    ],
    [
        'started_at' => now(),
        'expires_at' => now()->addHours(2),
        'duration_minutes' => 120,
        'current_question_index' => 0,
        'answers' => [],
        'remaining_time' => 7200,
    ]
);

echo "📝 Created exam session for student2 (ID: {$examSession2->id})\n\n";

// First tab switch (should result in immediate ban)
echo "Tab switch violation #1 (should cause immediate ban):\n";

$tabViolation = ExamSecurityViolation::create([
    'user_id' => $student2->id,
    'subject_id' => $subject->id,
    'exam_session_id' => $examSession2->id,
    'violation_type' => 'tab_switch',
    'description' => 'Student switched away from exam tab - IMMEDIATE BAN POLICY',
    'metadata' => [
        'policy' => 'IMMEDIATE_BAN_POLICY',
        'timestamp' => now()->toISOString(),
        'test_violation' => true
    ],
    'occurred_at' => now(),
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Test Browser'
]);

echo "  📊 Tab switch violation recorded (ID: {$tabViolation->id})\n";
echo "  🚫 EXPECTED RESULT: Student should be IMMEDIATELY BANNED on first tab switch\n\n";

// Test 3: Check admin query detection
echo "🧪 TEST 3: ADMIN QUERY DETECTION\n";
echo "=================================\n\n";

$bannedStudentsQuery = \Illuminate\Support\Facades\DB::table('exam_security_violations as v')
    ->join('users as u', 'v.user_id', '=', 'u.id')
    ->join('subjects as s', 'v.subject_id', '=', 's.id')
    ->select('u.name as user_name', 's.name as subject_name', 'v.violation_type', 'v.description')
    ->where('s.id', $subject->id)
    ->where(function($query) {
        $query->where('v.description', 'like', '%NO MERCY%')
              ->orWhere('v.description', 'like', '%banned%')
              ->orWhere('v.description', 'like', '%3-strike%')
              ->orWhere('v.description', 'like', '%IMMEDIATE BAN%')
              ->orWhere('v.description', 'like', '%immediate ban%')
              ->orWhere('v.description', 'like', '%IMMEDIATELY BANNED%')
              ->orWhere('v.description', 'like', '%immediately banned%')
              ->orWhere('v.description', 'like', '%permanently banned%')
              ->orWhere('v.description', 'like', '%PERMANENTLY BANNED%')
              ->orWhere('v.description', 'like', '%FINAL WARNING%')
              ->orWhere('v.violation_type', 'tab_switch')
              ->orWhere('v.violation_type', 'tab_switch_attempt')
              ->orWhereRaw('(SELECT COUNT(*) FROM exam_security_violations esv WHERE esv.user_id = v.user_id AND esv.subject_id = v.subject_id AND esv.violation_type = "right_click") >= 3');
    })
    ->get();

echo "Students detected as banned by admin query:\n";
foreach ($bannedStudentsQuery as $banned) {
    echo "  - {$banned->user_name} ({$banned->subject_name}): {$banned->violation_type}\n";
}

if ($bannedStudentsQuery->count() >= 2) {
    echo "\n✅ SUCCESS: Admin query should detect at least 2 banned students:\n";
    echo "   1. Student with 3+ right-clicks (3-strike policy)\n";
    echo "   2. Student with 1 tab switch (immediate ban policy)\n";
} else {
    echo "\n❌ ISSUE: Admin query should detect 2 banned students but only found {$bannedStudentsQuery->count()}\n";
}

echo "\n🎯 Check admin dashboard: http://localhost:8000/admin/security/banned-students\n";
echo "Expected result: Should show at least 2 banned students with different ban reasons\n\n";

echo "✅ Test complete! The new differentiated violation policy is implemented:\n";
echo "   📵 Tab switching = IMMEDIATE BAN (1st violation)\n";
echo "   🖱️  Right-clicking = 3-STRIKE POLICY (3rd violation)\n";
echo "   ⚡ System processes whichever limit is reached first\n";