<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 TESTING ADMIN FILTERING LOGIC\n";
echo "================================\n\n";

// Get violation-based bans (students who should be banned)
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

echo "1. Found {$violationBasedBans->count()} students who should be banned\n\n";

// Test the filtering logic for each student
$filteredOut = 0;
$stillBanned = 0;

foreach ($violationBasedBans as $student) {
    echo "Checking: {$student->user_name} - {$student->subject_name}\n";
    
    // Check if this student has been reactivated for this subject
    $reactivationRecords = DB::table('exam_security_violations')
        ->where('user_id', $student->user_id)
        ->where('subject_id', $student->subject_id)
        ->whereIn('violation_type', ['admin_reactivation', 'admin_bulk_reactivation'])
        ->orderBy('occurred_at', 'desc')
        ->get();
    
    if ($reactivationRecords->count() > 0) {
        echo "  ❌ HAS REACTIVATION RECORDS ({$reactivationRecords->count()})\n";
        echo "  Latest reactivation: {$reactivationRecords->first()->occurred_at}\n";
        
        // Check latest violation after reactivation
        $latestViolationAfterReactivation = DB::table('exam_security_violations')
            ->where('user_id', $student->user_id)
            ->where('subject_id', $student->subject_id)
            ->whereIn('violation_type', ['tab_switch', 'tab_switch_attempt'])
            ->where('occurred_at', '>', $reactivationRecords->first()->occurred_at)
            ->orderBy('occurred_at', 'desc')
            ->first();
        
        if ($latestViolationAfterReactivation) {
            echo "  ⚠️  NEW VIOLATIONS AFTER REACTIVATION!\n";
            echo "  Latest violation: {$latestViolationAfterReactivation->occurred_at}\n";
            echo "  → SHOULD STILL BE BANNED\n";
            $stillBanned++;
        } else {
            echo "  ✅ No violations after reactivation - correctly filtered out\n";
            $filteredOut++;
        }
    } else {
        echo "  ✅ NO REACTIVATION - SHOULD BE BANNED\n";
        $stillBanned++;
    }
    echo "\n";
}

echo "SUMMARY:\n";
echo "- Students correctly filtered out (reactivated, no new violations): {$filteredOut}\n";
echo "- Students who should still be banned: {$stillBanned}\n";
echo "- Admin dashboard shows: 0 (WRONG!)\n\n";

if ($stillBanned > 0) {
    echo "🚨 PROBLEM IDENTIFIED:\n";
    echo "The filtering logic is TOO AGGRESSIVE. It filters out students with ANY\n";
    echo "reactivation record, even if they have NEW violations after reactivation.\n\n";
    echo "SOLUTION: Only filter out students whose LATEST reactivation is AFTER\n";
    echo "their LATEST violation for that subject.\n";
} else {
    echo "✅ Filtering logic appears correct.\n";
}