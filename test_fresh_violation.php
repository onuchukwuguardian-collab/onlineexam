<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSession;
use App\Models\ExamSecurityViolation;
use Illuminate\Support\Facades\DB;

echo "🚨 TESTING FRESH TAB SWITCHING VIOLATION\n";
echo "=======================================\n\n";

// Find a test student
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
    ->whereIn('violation_type', ['tab_switch', 'tab_switch_attempt', 'admin_reactivation'])
    ->delete();

// Create a fresh exam session
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

// Simulate a fresh tab switch violation (immediate ban policy)
echo "🚨 Simulating tab switch violation...\n";
$violation = ExamSecurityViolation::create([
    'user_id' => $student->id,
    'subject_id' => $subject->id,
    'exam_session_id' => $examSession->id,
    'violation_type' => 'tab_switch',
    'description' => 'Student switched away from exam tab - immediate ban policy',
    'metadata' => [
        'policy' => 'IMMEDIATE_BAN_POLICY',
        'timestamp' => now()->toISOString(),
        'test_violation' => true
    ],
    'occurred_at' => now(),
    'ip_address' => '127.0.0.1',
    'user_agent' => 'Test Browser'
]);

echo "✅ Violation recorded (ID: {$violation->id})\n\n";

// Test if this student now appears in the admin query
echo "🔍 Testing if student appears in admin banned students query...\n";

$adminQuery = DB::table('exam_security_violations as v')
    ->join('users as u', 'v.user_id', '=', 'u.id')
    ->join('subjects as s', 'v.subject_id', '=', 's.id')
    ->select('u.name as user_name', 's.name as subject_name', 'v.violation_type', 'v.description')
    ->where('u.id', $student->id)
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
              ->orWhere('v.violation_type', 'tab_switch_attempt');
    })
    ->first();

if ($adminQuery) {
    echo "✅ SUCCESS! Student found in admin query:\n";
    echo "   Name: {$adminQuery->user_name}\n";
    echo "   Subject: {$adminQuery->subject_name}\n";
    echo "   Type: {$adminQuery->violation_type}\n";
    echo "   Description: {$adminQuery->description}\n\n";
    
    // Check if they have any reactivation records that would filter them out
    $hasReactivation = DB::table('exam_security_violations')
        ->where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->whereIn('violation_type', ['admin_reactivation', 'admin_bulk_reactivation'])
        ->exists();
    
    if ($hasReactivation) {
        echo "⚠️  Student has reactivation records - checking if they would be filtered...\n";
        
        $latestReactivation = DB::table('exam_security_violations')
            ->where('user_id', $student->id)
            ->where('subject_id', $subject->id)
            ->whereIn('violation_type', ['admin_reactivation', 'admin_bulk_reactivation'])
            ->orderBy('occurred_at', 'desc')
            ->first();
        
        $latestViolation = DB::table('exam_security_violations')
            ->where('user_id', $student->id)
            ->where('subject_id', $subject->id)
            ->whereIn('violation_type', ['tab_switch', 'tab_switch_attempt'])
            ->orderBy('occurred_at', 'desc')
            ->first();
        
        $reactivationTime = \Carbon\Carbon::parse($latestReactivation->occurred_at);
        $violationTime = \Carbon\Carbon::parse($latestViolation->occurred_at);
        
        if ($reactivationTime->greaterThan($violationTime)) {
            echo "❌ Would be filtered out (reactivation more recent)\n";
        } else {
            echo "✅ Would NOT be filtered out (violation more recent)\n";
        }
    } else {
        echo "✅ No reactivation records - would appear in banned list\n";
    }
    
} else {
    echo "❌ Student NOT found in admin query\n";
    echo "This suggests the query pattern matching is not working\n";
}

echo "\n🎯 Check admin dashboard: http://localhost:8000/admin/security/banned-students\n";
echo "Expected result: Should show at least 2 banned students now\n";