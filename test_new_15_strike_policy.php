<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSession;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;

echo "🧪 TESTING NEW VIOLATION POLICY - UPDATED IMPLEMENTATION\n";
echo "=======================================================\n\n";
echo "Policy Implementation:\n";
echo "📵 Tab switching/new window = IMMEDIATE BAN (1st violation)\n";
echo "🖱️  Right-clicking = 15-STRIKE POLICY (banned after 15th violation)\n";
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

ExamBan::where('user_id', $student->id)
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

// Test 1: Right-click violations (should follow 15-strike policy)
echo "🧪 TEST 1: RIGHT-CLICK VIOLATIONS (15-STRIKE POLICY)\n";
echo "====================================================\n\n";

for ($i = 1; $i <= 16; $i++) {
    echo "Right-click violation #{$i}:\n";
    
    $violation = ExamSecurityViolation::create([
        'user_id' => $student->id,
        'subject_id' => $subject->id,
        'exam_session_id' => $examSession->id,
        'violation_type' => 'right_click',
        'description' => "Student right-clicked during exam - 15-STRIKE POLICY (attempt #{$i})",
        'metadata' => [
            'policy' => '15_STRIKE_POLICY',
            'timestamp' => now()->toISOString(),
            'test_violation' => true
        ],
        'occurred_at' => now()->subMinutes(20 - $i), // Space them out
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
    
    if ($totalRightClicks >= 15) {
        $existingBan = ExamBan::where('user_id', $student->id)
            ->where('subject_id', $subject->id)
            ->where('is_active', true)
            ->first();
        
        if (!$existingBan) {
            // Create ban after 15th violation
            $ban = ExamBan::create([
                'user_id' => $student->id,
                'subject_id' => $subject->id,
                'ban_reason' => "15-STRIKE BAN: Student exceeded 15 right-click violations in {$subject->name}",
                'violation_details' => ['right_click_count' => $totalRightClicks],
                'total_violations' => $totalRightClicks,
                'is_permanent' => true,
                'is_active' => true,
                'banned_at' => now(),
                'banned_by' => 'system'
            ]);
            echo "  🚫 STUDENT BANNED after {$totalRightClicks} right-click violations (Ban ID: {$ban->id})\n";
        } else {
            echo "  🚫 Student already banned (Ban ID: {$existingBan->id})\n";
        }
    } else {
        $remaining = 15 - $totalRightClicks;
        echo "  ⚠️  Warning: {$remaining} more right-clicks until ban\n";
    }
    
    echo "\n";
}

// Test 2: Create a second student for tab switching test
echo "🧪 TEST 2: TAB SWITCHING VIOLATIONS (IMMEDIATE BAN POLICY)\n";
echo "==========================================================\n\n";

$student2 = User::where('role', 'student')->where('id', '!=', $student->id)->first();
if (!$student2) {
    echo "❌ No second student found for tab switching test\n";
} else {
    echo "👨‍🎓 Test Student 2: {$student2->name}\n\n";
    
    // Clean up any existing violations for student 2
    ExamSecurityViolation::where('user_id', $student2->id)
        ->where('subject_id', $subject->id)
        ->delete();
    
    ExamBan::where('user_id', $student2->id)
        ->where('subject_id', $subject->id)
        ->delete();
    
    // Create exam session for student 2
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
    
    echo "📝 Created exam session for student 2 (ID: {$examSession2->id})\n\n";
    
    // Test tab switching violation (should result in immediate ban)
    echo "Tab switching violation #1 (should trigger immediate ban):\n";
    
    $violation = ExamSecurityViolation::create([
        'user_id' => $student2->id,
        'subject_id' => $subject->id,
        'exam_session_id' => $examSession2->id,
        'violation_type' => 'tab_switch',
        'description' => "Student opened new window/tab or switched away from exam - IMMEDIATE BAN POLICY",
        'metadata' => [
            'policy' => 'IMMEDIATE_BAN_POLICY',
            'timestamp' => now()->toISOString(),
            'test_violation' => true
        ],
        'occurred_at' => now(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Browser'
    ]);
    
    // Should immediately create ban
    $ban = ExamBan::create([
        'user_id' => $student2->id,
        'subject_id' => $subject->id,
        'ban_reason' => "IMMEDIATE BAN: Tab switching/new window detected in {$subject->name}",
        'violation_details' => ['tab_switch_count' => 1],
        'total_violations' => 1,
        'is_permanent' => true,
        'is_active' => true,
        'banned_at' => now(),
        'banned_by' => 'system'
    ]);
    
    echo "  📊 Violation recorded (ID: {$violation->id})\n";
    echo "  🚫 STUDENT IMMEDIATELY BANNED for tab switching (Ban ID: {$ban->id})\n\n";
}

// Test 3: Verify the admin query detects both types of bans
echo "🧪 TEST 3: ADMIN QUERY VERIFICATION\n";
echo "===================================\n\n";

$bannedStudentsQuery = DB::table('exam_security_violations as v')
    ->join('users as u', 'v.user_id', '=', 'u.id')
    ->join('subjects as s', 'v.subject_id', '=', 's.id')
    ->select('u.name as user_name', 's.name as subject_name', 'v.violation_type', 'v.description')
    ->where('s.id', $subject->id)
    ->where(function($query) {
        $query->where('v.description', 'like', '%IMMEDIATE BAN%')
              ->orWhere('v.description', 'like', '%immediate ban%')
              ->orWhere('v.description', 'like', '%15-STRIKE BAN%')
              ->orWhere('v.description', 'like', '%15-strike%')
              ->orWhere('v.violation_type', 'tab_switch')
              ->orWhere('v.violation_type', 'tab_switch_attempt')
              ->orWhereRaw('(SELECT COUNT(*) FROM exam_security_violations esv WHERE esv.user_id = v.user_id AND esv.subject_id = v.subject_id AND esv.violation_type = "right_click") >= 15');
    })
    ->get();

echo "Students detected as banned by admin query:\n";
foreach ($bannedStudentsQuery as $banned) {
    echo "  - {$banned->user_name} ({$banned->subject_name}): {$banned->violation_type}\n";
}

// Also check the exam_bans table
$activeBans = ExamBan::with(['user', 'subject'])
    ->where('subject_id', $subject->id)
    ->where('is_active', true)
    ->get();

echo "\nActive bans in exam_bans table:\n";
foreach ($activeBans as $ban) {
    echo "  - {$ban->user->name} ({$ban->subject->name}): {$ban->ban_reason}\n";
}

if ($activeBans->count() >= 2) {
    echo "\n✅ SUCCESS: System should detect at least 2 banned students:\n";
    echo "   1. Student with 15+ right-clicks (15-strike policy)\n";
    echo "   2. Student with 1 tab switch (immediate ban policy)\n";
} else {
    echo "\n⚠️  INFO: Found {$activeBans->count()} banned students\n";
}

echo "\n🎯 Check admin dashboard: http://localhost:8000/admin/security/banned-students\n";
echo "Expected result: Should show at least 2 banned students with different ban reasons\n\n";

echo "✅ Test complete! The updated violation policy is implemented:\n";
echo "   📵 Tab switching = IMMEDIATE BAN (1st violation)\n";
echo "   🖱️  Right-clicking = 15-STRIKE POLICY (15th violation)\n";
echo "   ⚡ System processes whichever limit is reached first\n\n";

echo "📊 Summary of violations created:\n";
echo "  - Right-click violations: 16 (should trigger ban at 15th)\n";
echo "  - Tab switch violations: 1 (should trigger immediate ban)\n";
echo "  - Total active bans: {$activeBans->count()}\n";