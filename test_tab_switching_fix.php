<?php

use Illuminate\Support\Facades\Artisan;

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSession;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use Illuminate\Support\Facades\DB;

echo "🧪 TESTING TAB SWITCHING DETECTION FIX\n";
echo "=====================================\n\n";

// Step 1: Find or create test student
$student = User::where('role', 'student')->first();
if (!$student) {
    echo "❌ No student user found. Please ensure you have student users in the database.\n";
    exit(1);
}

// Step 2: Find test subject
$subject = Subject::first();
if (!$subject) {
    echo "❌ No subject found. Please ensure you have subjects in the database.\n";
    exit(1);
}

echo "👨‍🎓 Test Student: {$student->name} (ID: {$student->id})\n";
echo "📚 Test Subject: {$subject->name} (ID: {$subject->id})\n\n";

// Step 3: Clean up previous test data
echo "🧹 Cleaning up previous test data...\n";
ExamSecurityViolation::where('user_id', $student->id)
    ->where('subject_id', $subject->id)
    ->delete();
ExamBan::where('user_id', $student->id)
    ->where('subject_id', $subject->id)
    ->delete();

// Step 4: Create a test exam session
echo "📝 Creating test exam session...\n";
$examSession = ExamSession::create([
    'user_id' => $student->id,
    'subject_id' => $subject->id,
    'started_at' => now(),
    'expires_at' => now()->addHours(2),
    'duration_minutes' => 120,
    'is_active' => true,
    'current_question_index' => 0,
    'answers' => [],
    'remaining_time' => 7200, // 2 hours in seconds
]);

echo "✅ Exam session created (ID: {$examSession->id})\n\n";

// Step 5: Test violation types
$testViolations = [
    ['type' => 'tab_switch', 'desc' => 'Student switched away from exam tab - immediate ban policy'],
    ['type' => 'tab_switch_attempt', 'desc' => 'Student attempted tab switching via keyboard shortcut - immediate ban policy'],
    ['type' => 'navigation_attempt', 'desc' => 'Middle-click navigation attempt blocked'],
    ['type' => 'window_open_attempt', 'desc' => 'window.open() navigation attempt blocked']
];

echo "🚨 Testing violation recording...\n";
foreach ($testViolations as $violationData) {
    echo "   Testing {$violationData['type']}... ";
    
    try {
        $violation = ExamSecurityViolation::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'exam_session_id' => $examSession->id,
            'violation_type' => $violationData['type'],
            'description' => $violationData['desc'],
            'metadata' => [
                'policy' => 'IMMEDIATE_BAN_POLICY',
                'timestamp' => now()->toISOString(),
                'test_mode' => true
            ],
            'occurred_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser'
        ]);
        
        echo "✅ SUCCESS (ID: {$violation->id})\n";
    } catch (\Exception $e) {
        echo "❌ FAILED: {$e->getMessage()}\n";
    }
}

// Step 6: Test admin query
echo "\n🔍 Testing admin banned students query...\n";

try {
    // Simulate the banned students query
    $noMercyQuery = DB::table('exam_security_violations as v')
        ->join('users as u', 'v.user_id', '=', 'u.id')
        ->join('subjects as s', 'v.subject_id', '=', 's.id')
        ->select(
            DB::raw("CONCAT('violation_', v.user_id, '_', v.subject_id) as ban_id"),
            'u.id as user_id',
            'u.name as user_name',
            'u.email as user_email', 
            'u.registration_number',
            's.id as subject_id',
            's.name as subject_name',
            DB::raw('MAX(v.description) as ban_reason'),
            DB::raw('MAX(v.occurred_at) as banned_at'),
            DB::raw("'VIOLATION_BASED_BAN' as source_type")
        )
        ->where('v.user_id', $student->id)
        ->where('v.subject_id', $subject->id)
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
                  ->orWhereRaw('(SELECT COUNT(*) FROM exam_security_violations esv WHERE esv.user_id = v.user_id AND esv.subject_id = v.subject_id AND esv.violation_type IN ("tab_switch", "tab_switch_attempt")) >= 1');
        })
        ->groupBy('v.user_id', 'v.subject_id', 'u.name', 'u.email', 'u.registration_number', 's.name')
        ->get();

    if ($noMercyQuery->count() > 0) {
        echo "✅ Admin query found {$noMercyQuery->count()} banned student(s)\n";
        foreach ($noMercyQuery as $ban) {
            echo "   - {$ban->user_name} banned from {$ban->subject_name}\n";
            echo "     Reason: {$ban->ban_reason}\n";
        }
    } else {
        echo "❌ Admin query found no banned students (this might be wrong)\n";
    }
} catch (\Exception $e) {
    echo "❌ Admin query failed: {$e->getMessage()}\n";
}

// Step 7: Check violations exist
echo "\n📊 Checking recorded violations...\n";
$violations = ExamSecurityViolation::where('user_id', $student->id)
    ->where('subject_id', $subject->id)
    ->get();

echo "Found {$violations->count()} violations:\n";
foreach ($violations as $v) {
    echo "   - {$v->violation_type}: {$v->description}\n";
}

echo "\n✅ Test completed! Check the admin dashboard at:\n";
echo "   http://localhost:8000/admin/security/banned-students\n\n";