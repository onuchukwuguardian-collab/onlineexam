<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🧪 TESTING FIXED ADMIN FILTERING LOGIC\n";
echo "======================================\n\n";

// Simulate the exact same logic as the fixed admin controller
$violationBasedBans = DB::table('exam_security_violations as v')
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
    ->groupBy('v.user_id', 'v.subject_id', 'u.name', 'u.email', 'u.registration_number', 's.name')
    ->get();

echo "1. Initial query found {$violationBasedBans->count()} potential banned students\n\n";

// Apply the FIXED filtering logic
$filteredStudents = $violationBasedBans->filter(function($student) {
    // Get the latest reactivation for this student+subject
    $latestReactivation = DB::table('exam_security_violations')
        ->where('user_id', $student->user_id)
        ->where('subject_id', $student->subject_id)
        ->whereIn('violation_type', ['admin_reactivation', 'admin_bulk_reactivation'])
        ->orderBy('occurred_at', 'desc')
        ->first();
    
    if (!$latestReactivation) {
        echo "✅ {$student->user_name} - {$student->subject_name}: No reactivation - BANNED\n";
        return true;
    }
    
    // Get the latest violation for this student+subject
    $latestViolation = DB::table('exam_security_violations')
        ->where('user_id', $student->user_id)
        ->where('subject_id', $student->subject_id)
        ->whereIn('violation_type', ['tab_switch', 'tab_switch_attempt'])
        ->orderBy('occurred_at', 'desc')
        ->first();
    
    if (!$latestViolation) {
        echo "❌ {$student->user_name} - {$student->subject_name}: No violations - EXCLUDED\n";
        return false;
    }
    
    // Compare timestamps
    $reactivationTime = \Carbon\Carbon::parse($latestReactivation->occurred_at);
    $violationTime = \Carbon\Carbon::parse($latestViolation->occurred_at);
    
    if ($reactivationTime->greaterThan($violationTime)) {
        echo "❌ {$student->user_name} - {$student->subject_name}: Reactivation more recent - EXCLUDED\n";
        echo "   Latest violation: {$violationTime->toDateTimeString()}\n";
        echo "   Latest reactivation: {$reactivationTime->toDateTimeString()}\n";
        return false;
    } else {
        echo "✅ {$student->user_name} - {$student->subject_name}: New violations after reactivation - BANNED\n";
        echo "   Latest violation: {$violationTime->toDateTimeString()}\n";
        echo "   Latest reactivation: {$reactivationTime->toDateTimeString()}\n";
        return true;
    }
});

echo "\n2. After filtering: {$filteredStudents->count()} students should be shown as banned\n\n";

if ($filteredStudents->count() > 0) {
    echo "✅ FIXED! These students should now appear in admin dashboard:\n";
    foreach ($filteredStudents as $student) {
        echo "   - {$student->user_name} banned from {$student->subject_name}\n";
    }
} else {
    echo "ℹ️  No students should be banned (all have been properly reactivated)\n";
}

echo "\n🎯 Now check: http://localhost:8000/admin/security/banned-students\n";