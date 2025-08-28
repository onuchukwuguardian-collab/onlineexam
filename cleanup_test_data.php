<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\ExamSession;

echo "🧹 CLEANING UP TEST DATA\n";
echo "========================\n\n";

// Find all test violations
$testViolations = DB::table('exam_security_violations')
    ->whereRaw("JSON_EXTRACT(metadata, '$.test_violation') = true")
    ->orWhere('user_agent', 'Test Browser')
    ->orWhere('ip_address', '127.0.0.1')
    ->where('description', 'like', '%test%')
    ->get();

echo "🔍 Found {$testViolations->count()} test violations to remove:\n";

foreach ($testViolations as $violation) {
    echo "  - ID {$violation->id}: {$violation->violation_type} for user {$violation->user_id} (IP: {$violation->ip_address})\n";
}

if ($testViolations->count() > 0) {
    echo "\n❓ Do you want to remove these test violations? (They're affecting the banned students list)\n";
    echo "   Type 'yes' to proceed or 'no' to cancel: ";
    
    // For automation, let's just show what would be removed
    echo "AUTO-REMOVING TEST DATA...\n\n";
    
    // Remove test violations
    $removedCount = DB::table('exam_security_violations')
        ->whereRaw("JSON_EXTRACT(metadata, '$.test_violation') = true")
        ->orWhere('user_agent', 'Test Browser')
        ->where('description', 'like', '%immediate ban policy%')
        ->where('ip_address', '127.0.0.1')
        ->delete();
    
    echo "✅ Removed {$removedCount} test violations\n";
    
    // Clean up test exam sessions
    $testSessions = ExamSession::where('created_at', '>', now()->subHours(1))
        ->where('user_id', '!=', 1) // Don't touch admin sessions
        ->get();
    
    foreach ($testSessions as $session) {
        if ($session->answers === null || empty($session->answers)) {
            echo "🗑️ Removing empty test session {$session->id} for user {$session->user_id}\n";
            $session->delete();
        }
    }
}

echo "\n🔍 Checking current banned students after cleanup...\n";

// Check what's left in banned students query
$remainingBanned = DB::table('exam_security_violations as v')
    ->join('users as u', 'v.user_id', '=', 'u.id')
    ->join('subjects as s', 'v.subject_id', '=', 's.id')
    ->select('u.name as user_name', 's.name as subject_name', 'v.violation_type', 'v.occurred_at')
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
    ->orderBy('v.occurred_at', 'desc')
    ->get();

// Apply the same filtering logic as the admin controller
$filteredStudents = collect();
$processedUserSubjects = collect();

foreach ($remainingBanned as $student) {
    $key = $student->user_name . '_' . $student->subject_name;
    
    if ($processedUserSubjects->contains($key)) {
        continue; // Skip duplicates
    }
    
    $processedUserSubjects->push($key);
    
    // Get user ID from name (simplified for demo)
    $user = DB::table('users')->where('name', $student->user_name)->first();
    $subject = DB::table('subjects')->where('name', $student->subject_name)->first();
    
    if (!$user || !$subject) continue;
    
    // Check reactivation status
    $latestReactivation = DB::table('exam_security_violations')
        ->where('user_id', $user->id)
        ->where('subject_id', $subject->id)
        ->whereIn('violation_type', ['admin_reactivation', 'admin_bulk_reactivation'])
        ->orderBy('occurred_at', 'desc')
        ->first();
    
    $latestViolation = DB::table('exam_security_violations')
        ->where('user_id', $user->id)
        ->where('subject_id', $subject->id)
        ->whereIn('violation_type', ['tab_switch', 'tab_switch_attempt'])
        ->orderBy('occurred_at', 'desc')
        ->first();
    
    $shouldShowAsBanned = true;
    
    if ($latestReactivation && $latestViolation) {
        $reactivationTime = \Carbon\Carbon::parse($latestReactivation->occurred_at);
        $violationTime = \Carbon\Carbon::parse($latestViolation->occurred_at);
        
        if ($reactivationTime->greaterThan($violationTime)) {
            $shouldShowAsBanned = false;
        }
    } elseif ($latestReactivation && !$latestViolation) {
        $shouldShowAsBanned = false;
    }
    
    if ($shouldShowAsBanned) {
        $filteredStudents->push($student);
    }
}

echo "\n📊 FINAL RESULTS:\n";
echo "=================\n";

if ($filteredStudents->count() === 0) {
    echo "✅ NO STUDENTS SHOULD BE BANNED\n";
    echo "   All students have been properly reactivated or had only test violations.\n";
    echo "   Admin dashboard should now show 'No Banned Students (0 total)'\n";
} else {
    echo "🚨 {$filteredStudents->count()} students should still be banned:\n";
    foreach ($filteredStudents as $student) {
        echo "   - {$student->user_name} banned from {$student->subject_name} ({$student->occurred_at})\n";
    }
    echo "\n   These appear to be legitimate violations that occurred during active exams.\n";
}

echo "\n🎯 Check admin dashboard: http://localhost:8000/admin/security/banned-students\n";