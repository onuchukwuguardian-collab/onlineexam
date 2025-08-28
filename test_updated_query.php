<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Subject;

echo "=== TESTING UPDATED BANNED STUDENTS QUERY ===\n\n";

$user = User::where('email', 'john.ade@example.com')->first();
$basicScience = Subject::where('name', 'like', '%Basic Science%')->first();
$culturalArts = Subject::where('name', 'like', '%Cultural%')->first();

echo "Testing for: {$user->name} (ID: {$user->id})\n";
echo "Basic Science ID: {$basicScience->id}\n";
echo "Cultural Arts ID: {$culturalArts->id}\n\n";

// STEP 1: Test the updated formal bans query
echo "=== TESTING FORMAL BANS QUERY (Updated) ===\n";
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
        'b.created_at as banned_at',
        DB::raw("CASE WHEN b.is_active = 1 THEN 'ACTIVE_BAN' ELSE 'INACTIVE_BAN' END as source_type"),
        'b.is_active',
        'b.reactivated_at'
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
echo "Formal bans found: " . count($formalBans) . "\n";
foreach ($formalBans as $ban) {
    echo "- {$ban->subject_name}: {$ban->source_type} (Active: " . ($ban->is_active ? 'Yes' : 'No') . ")\n";
    echo "  Reactivated: " . ($ban->reactivated_at ?: 'Never') . "\n";
}

// STEP 2: Test the updated violations query
echo "\n=== TESTING VIOLATIONS QUERY (Updated) ===\n";
$violationsQuery = DB::table('exam_security_violations as v')
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
        'v.description as ban_reason',
        'v.occurred_at as banned_at',
        DB::raw("'VIOLATION_BASED_BAN' as source_type"),
        DB::raw("COUNT(v.id) as violation_count")
    )
    ->where('u.id', $user->id)
    ->where(function($query) {
        $query->where('v.description', 'like', '%NO MERCY%')
              ->orWhere('v.description', 'like', '%banned%')
              ->orWhere('v.description', 'like', '%3-strike%')
              ->orWhere('v.description', 'like', '%IMMEDIATE BAN%')
              ->orWhere('v.description', 'like', '%permanently banned%')
              ->orWhere('v.description', 'like', '%FINAL WARNING%')
              // Also check for users with 3+ violations for same subject
              ->orWhereRaw('(SELECT COUNT(*) FROM exam_security_violations esv WHERE esv.user_id = v.user_id AND esv.subject_id = v.subject_id AND esv.violation_type = "tab_switch") >= 3');
    })
    ->groupBy('v.user_id', 'v.subject_id', 'u.name', 'u.email', 'u.registration_number', 's.name', 'v.description', 'v.occurred_at')
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('exam_bans')
              ->whereColumn('exam_bans.user_id', 'v.user_id')
              ->whereColumn('exam_bans.subject_id', 'v.subject_id')
              ->where('exam_bans.is_active', true)
              ->whereNull('exam_bans.reactivated_at');
    });

$violationBans = $violationsQuery->get();
echo "Violation-based bans found: " . count($violationBans) . "\n";
foreach ($violationBans as $ban) {
    echo "- {$ban->subject_name}: {$ban->ban_reason}\n";
    echo "  Violation count: {$ban->violation_count}\n";
}

// STEP 3: Test specific subjects
echo "\n=== SPECIFIC SUBJECT TESTS ===\n";

// Check Cultural Creative Arts specifically
$culturalViolationCount = DB::table('exam_security_violations')
    ->where('user_id', $user->id)
    ->where('subject_id', $culturalArts->id)
    ->where('violation_type', 'tab_switch')
    ->count();

echo "Cultural Creative Arts total violations: {$culturalViolationCount}\n";

$hasActiveCulturalBan = DB::table('exam_bans')
    ->where('user_id', $user->id)
    ->where('subject_id', $culturalArts->id)
    ->where('is_active', true)
    ->whereNull('reactivated_at')
    ->exists();

echo "Cultural Creative Arts has active formal ban: " . ($hasActiveCulturalBan ? 'Yes' : 'No') . "\n";

if ($culturalViolationCount >= 3 && !$hasActiveCulturalBan) {
    echo "✅ Cultural Creative Arts should appear in violation-based bans!\n";
} else {
    echo "❌ Cultural Creative Arts should NOT appear in violation-based bans\n";
    echo "   Reason: " . ($culturalViolationCount < 3 ? "Not enough violations ({$culturalViolationCount})" : "Has active formal ban") . "\n";
}

echo "\n=== FINAL COMBINED RESULTS ===\n";
$totalResults = count($formalBans) + count($violationBans);
echo "Total banned entries for ADEBANYO: {$totalResults}\n";
echo "- Formal bans: " . count($formalBans) . "\n";
echo "- Violation-based bans: " . count($violationBans) . "\n";

if ($totalResults > 0) {
    echo "✅ ADEBANYO will appear in the banned students dashboard!\n";
} else {
    echo "❌ ADEBANYO will NOT appear in the banned students dashboard!\n";
}