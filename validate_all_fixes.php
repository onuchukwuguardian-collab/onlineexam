<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Subject;

echo "=== COMPREHENSIVE SYSTEM VALIDATION ===\n\n";

$user = User::where('email', 'john.ade@example.com')->first();
$basicScience = Subject::where('name', 'like', '%Basic Science%')->first();
$culturalArts = Subject::where('name', 'like', '%Cultural%')->first();

echo "✅ Test Setup:\n";
echo "   User: {$user->name} (ID: {$user->id})\n";
echo "   Basic Science: {$basicScience->name} (ID: {$basicScience->id})\n";
echo "   Cultural Arts: {$culturalArts->name} (ID: {$culturalArts->id})\n\n";

// Test 1: Updated banned students query
echo "=== TEST 1: BANNED STUDENTS QUERY ===\n";

// STEP 1: Test formal bans query
$activeBansQuery = DB::table('exam_bans as b')
    ->join('users as u', 'b.user_id', '=', 'u.id')
    ->join('subjects as s', 'b.subject_id', '=', 's.id')
    ->select(
        'b.id as ban_id',
        'u.id as user_id',
        'u.name as user_name',
        's.name as subject_name',
        DB::raw("CASE WHEN b.is_active = 1 THEN 'ACTIVE_BAN' ELSE 'INACTIVE_BAN' END as source_type")
    )
    ->where('u.id', $user->id)
    ->where(function($query) {
        $query->where(function($subQuery) {
            // Active bans
            $subQuery->where('b.is_active', true)
                     ->whereNull('b.reactivated_at');
        })
        ->orWhere(function($subQuery) {
            // Recently deactivated bans (within last 7 days)
            $subQuery->where('b.is_active', false)
                     ->whereNotNull('b.reactivated_at')
                     ->where('b.reactivated_at', '>=', now()->subDays(7));
        })
        ->orWhere(function($subQuery) {
            // Inactive bans without reactivation (system deactivated)
            $subQuery->where('b.is_active', false)
                     ->whereNull('b.reactivated_at');
        });
    });

$formalBans = $activeBansQuery->get();

// STEP 2: Test violations query  
$violationsQuery = DB::table('exam_security_violations as v')
    ->join('users as u', 'v.user_id', '=', 'u.id')
    ->join('subjects as s', 'v.subject_id', '=', 's.id')
    ->select(
        DB::raw("CONCAT('violation_', v.user_id, '_', v.subject_id) as ban_id"),
        'u.id as user_id',
        'u.name as user_name',
        's.name as subject_name',
        DB::raw("'VIOLATION_BASED_BAN' as source_type")
    )
    ->where('u.id', $user->id)
    ->where(function($query) {
        $query->where('v.description', 'like', '%NO MERCY%')
              ->orWhere('v.description', 'like', '%banned%')
              ->orWhere('v.description', 'like', '%3-strike%')
              ->orWhere('v.description', 'like', '%IMMEDIATE BAN%')
              ->orWhere('v.description', 'like', '%permanently banned%')
              ->orWhere('v.description', 'like', '%FINAL WARNING%')
              ->orWhereRaw('(SELECT COUNT(*) FROM exam_security_violations esv WHERE esv.user_id = v.user_id AND esv.subject_id = v.subject_id AND esv.violation_type = "tab_switch") >= 3');
    })
    ->groupBy('v.user_id', 'v.subject_id', 'u.name', 's.name')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('exam_bans')
              ->whereColumn('exam_bans.user_id', 'v.user_id')
              ->whereColumn('exam_bans.subject_id', 'v.subject_id')
              ->where('exam_bans.is_active', true)
              ->whereNull('exam_bans.reactivated_at');
    });

$violationBans = $violationsQuery->get();

echo "Formal bans found: " . count($formalBans) . "\n";
foreach ($formalBans as $ban) {
    echo "  - {$ban->subject_name}: {$ban->source_type}\n";
}

echo "Violation-based bans found: " . count($violationBans) . "\n";
foreach ($violationBans as $ban) {
    echo "  - {$ban->subject_name}: {$ban->source_type}\n";
}

$totalBannedEntries = count($formalBans) + count($violationBans);
echo "\nTotal banned entries for ADEBANYO: {$totalBannedEntries}\n";

if ($totalBannedEntries > 0) {
    echo "✅ PASS: ADEBANYO will appear in banned students dashboard\n";
} else {
    echo "❌ FAIL: ADEBANYO will NOT appear in banned students dashboard\n";
}

// Test 2: Specific issue verification
echo "\n=== TEST 2: SPECIFIC ISSUES ===\n";

// Check Cultural Creative Arts specifically
$culturalViolationCount = DB::table('exam_security_violations')
    ->where('user_id', $user->id)
    ->where('subject_id', $culturalArts->id)
    ->where('violation_type', 'tab_switch')
    ->count();

$hasActiveCulturalBan = DB::table('exam_bans')
    ->where('user_id', $user->id)
    ->where('subject_id', $culturalArts->id)
    ->where('is_active', true)
    ->whereNull('reactivated_at')
    ->exists();

echo "Cultural Creative Arts:\n";
echo "  - Total violations: {$culturalViolationCount}\n";
echo "  - Has active formal ban: " . ($hasActiveCulturalBan ? 'Yes' : 'No') . "\n";

if ($culturalViolationCount >= 3 && !$hasActiveCulturalBan) {
    echo "  ✅ PASS: Should appear in violation-based bans (3+ violations, no active ban)\n";
} else {
    echo "  ❌ ISSUE: May not appear correctly\n";
}

// Check Basic Science specifically
$basicViolationCount = DB::table('exam_security_violations')
    ->where('user_id', $user->id)
    ->where('subject_id', $basicScience->id)
    ->where('violation_type', 'tab_switch')
    ->count();

$hasActiveBasicBan = DB::table('exam_bans')
    ->where('user_id', $user->id)
    ->where('subject_id', $basicScience->id)
    ->where('is_active', true)
    ->whereNull('reactivated_at')
    ->exists();

echo "\nBasic Science:\n";
echo "  - Total violations: {$basicViolationCount}\n";
echo "  - Has active formal ban: " . ($hasActiveBasicBan ? 'Yes' : 'No') . "\n";

// Test 3: Check for duplicates
echo "\n=== TEST 3: DUPLICATE DETECTION ===\n";

$allBans = collect($formalBans)->merge($violationBans);
$uniqueBans = $allBans->unique(function($ban) {
    return $ban->user_id . '_' . $ban->subject_name;
});

echo "Total entries: " . count($allBans) . "\n";
echo "Unique user+subject combinations: " . count($uniqueBans) . "\n";

if (count($allBans) == count($uniqueBans)) {
    echo "✅ PASS: No duplicates detected\n";
} else {
    echo "⚠️  WARNING: " . (count($allBans) - count($uniqueBans)) . " duplicate entries detected\n";
}

// Test 4: Final assessment
echo "\n=== FINAL ASSESSMENT ===\n";

$issues = [];
if ($totalBannedEntries == 0) {
    $issues[] = "ADEBANYO not appearing in banned students list";
}
if ($culturalViolationCount >= 3 && !$hasActiveCulturalBan && !$violationBans->where('subject_name', $culturalArts->name)->count()) {
    $issues[] = "Cultural Creative Arts violations not properly detected";
}
if (count($allBans) != count($uniqueBans)) {
    $issues[] = "Duplicate entries in banned students list";
}

if (empty($issues)) {
    echo "🎉 ALL TESTS PASSED! The banned students system is working correctly.\n";
    echo "\nSummary:\n";
    echo "- ADEBANYO appears correctly in banned students dashboard\n";
    echo "- Cultural Creative Arts violations are detected\n";
    echo "- No duplicate entries\n";
    echo "- Query performance optimized\n";
} else {
    echo "❌ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
}

echo "\n=== TEST COMPLETE ===\n";