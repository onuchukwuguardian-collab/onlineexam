<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

echo "🔍 CHECKING DATABASE FOR VIOLATION RECORDS\n";
echo "==========================================\n\n";

// Check total violations
$totalViolations = ExamSecurityViolation::count();
echo "📊 Total violations in database: {$totalViolations}\n";

if ($totalViolations > 0) {
    echo "\n📋 Recent violations (last 10):\n";
    $recentViolations = ExamSecurityViolation::with(['user', 'subject'])
        ->orderBy('occurred_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($recentViolations as $violation) {
        echo "  - ID: {$violation->id}, User: " . ($violation->user->name ?? 'Unknown') . 
             ", Subject: " . ($violation->subject->name ?? 'Unknown') . 
             ", Type: {$violation->violation_type}, Date: {$violation->occurred_at}\n";
    }
    
    echo "\n📊 Violations by type:\n";
    $violationsByType = ExamSecurityViolation::select('violation_type', DB::raw('count(*) as count'))
        ->groupBy('violation_type')
        ->get();
    
    foreach ($violationsByType as $vType) {
        echo "  - {$vType->violation_type}: {$vType->count}\n";
    }
}

// Check active bans
$activeBans = ExamBan::where('is_active', true)->count();
echo "\n🚫 Active bans in database: {$activeBans}\n";

if ($activeBans > 0) {
    echo "\n📋 Active bans:\n";
    $bans = ExamBan::with(['user', 'subject'])
        ->where('is_active', true)
        ->get();
    
    foreach ($bans as $ban) {
        echo "  - ID: {$ban->id}, User: " . ($ban->user->name ?? 'Unknown') . 
             ", Subject: " . ($ban->subject->name ?? 'Unknown') . 
             ", Banned: {$ban->banned_at}\n";
    }
}

// Check what the admin query finds
echo "\n🔍 TESTING ADMIN QUERY LOGIC\n";
echo "============================\n";

// Test the same query used in the admin controller
$activeBansQuery = DB::table('exam_bans as b')
    ->join('users as u', 'b.user_id', '=', 'u.id')
    ->join('subjects as s', 'b.subject_id', '=', 's.id')
    ->select(
        'b.id as ban_id',
        'u.id as user_id',
        'u.name as user_name',
        'u.email as user_email', 
        'u.registration_number',
        's.id as subject_id',
        's.name as subject_name',
        'b.ban_reason',
        'b.created_at as banned_at'
    )
    ->where('b.is_active', true)
    ->whereNull('b.reactivated_at')
    ->get();

echo "Active bans from admin query: " . $activeBansQuery->count() . "\n";

// Test violation-based query
$violationQuery = DB::table('exam_security_violations as v')
    ->join('users as u', 'v.user_id', '=', 'u.id')
    ->join('subjects as s', 'v.subject_id', '=', 's.id')
    ->select('u.name as user_name', 's.name as subject_name', 'v.violation_type', 'v.description')
    ->where(function($query) {
        $query->where('v.description', 'like', '%NO MERCY%')
              ->orWhere('v.description', 'like', '%banned%')
              ->orWhere('v.description', 'like', '%15-STRIKE BAN%')
              ->orWhere('v.description', 'like', '%15-strike%')
              ->orWhere('v.description', 'like', '%IMMEDIATE BAN%')
              ->orWhere('v.description', 'like', '%immediate ban%')
              ->orWhere('v.description', 'like', '%IMMEDIATELY BANNED%')
              ->orWhere('v.description', 'like', '%immediately banned%')
              ->orWhere('v.description', 'like', '%permanently banned%')
              ->orWhere('v.description', 'like', '%PERMANENTLY BANNED%')
              ->orWhere('v.description', 'like', '%FINAL WARNING%')
              ->orWhere('v.violation_type', 'tab_switch')
              ->orWhere('v.violation_type', 'tab_switch_attempt')
              ->orWhereRaw('(SELECT COUNT(*) FROM exam_security_violations esv WHERE esv.user_id = v.user_id AND esv.subject_id = v.subject_id AND esv.violation_type = "right_click") >= 15');
    })
    ->get();

echo "Violation-based bans from admin query: " . $violationQuery->count() . "\n";

if ($violationQuery->count() > 0) {
    echo "\nViolation-based banned students:\n";
    foreach ($violationQuery as $v) {
        echo "  - {$v->user_name} ({$v->subject_name}): {$v->violation_type} - {$v->description}\n";
    }
}

// Summary
echo "\n📊 SUMMARY\n";
echo "==========\n";
echo "Total violations: {$totalViolations}\n";
echo "Active formal bans: {$activeBans}\n";
echo "Admin query active bans: " . $activeBansQuery->count() . "\n";
echo "Admin query violation-based: " . $violationQuery->count() . "\n";
echo "Combined total for admin: " . ($activeBansQuery->count() + $violationQuery->count()) . "\n";

if ($totalViolations == 0 && $activeBans == 0) {
    echo "\n❌ NO DATA FOUND: This explains why admin sees 'no records found'\n";
    echo "Need to create test violations to see data in admin interface.\n";
} else {
    echo "\n✅ DATA EXISTS: Admin should be able to see records\n";
    echo "If admin still shows 'no records found', there might be a view or routing issue.\n";
}