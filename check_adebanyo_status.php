<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;
use App\Models\Subject;

echo "=== ADEBANYO INVESTIGATION ===\n\n";

// Find ADEBANYO
$user = User::where('email', 'john.ade@example.com')->first();
if (!$user) {
    echo "❌ User john.ade@example.com not found\n";
    
    // Try to find any user with similar name
    echo "\nSearching for users with 'ADEBANYO' in name...\n";
    $users = User::where('name', 'like', '%ADEBANYO%')->get();
    foreach ($users as $u) {
        echo "Found: {$u->name} - {$u->email} (ID: {$u->id})\n";
    }
    exit;
}

echo "✅ Found User: {$user->name} (ID: {$user->id})\n";
echo "   Email: {$user->email}\n"; 
echo "   Registration: {$user->registration_number}\n";

// Check subjects
$subjects = Subject::all();
echo "\n=== ALL SUBJECTS ===\n";
foreach ($subjects as $subject) {
    echo "- {$subject->name} (ID: {$subject->id})\n";
}

$basicScience = Subject::where('name', 'like', '%Basic Science%')
    ->orWhere('name', 'like', '%Basic%')
    ->orWhere('name', 'like', '%Science%')
    ->first();
    
$culturalArts = Subject::where('name', 'like', '%Cultural%')
    ->orWhere('name', 'like', '%Creative%')
    ->orWhere('name', 'like', '%Arts%')
    ->first();

echo "\n=== TARGET SUBJECTS ===\n";
if ($basicScience) {
    echo "✅ Basic Science & Technology (ID: {$basicScience->id}): {$basicScience->name}\n";
} else {
    echo "❌ Basic Science & Technology not found\n";
}

if ($culturalArts) {
    echo "✅ Cultural Creative Arts (ID: {$culturalArts->id}): {$culturalArts->name}\n";
} else {
    echo "❌ Cultural Creative Arts not found\n";
}

// Check bans for ADEBANYO
echo "\n=== ADEBANYO FORMAL BANS ===\n";
$bans = ExamBan::where('user_id', $user->id)->with('subject')->get();
foreach ($bans as $ban) {
    $status = $ban->is_active ? 'ACTIVE' : 'INACTIVE';
    $reactivated = $ban->reactivated_at ? "(Reactivated: {$ban->reactivated_at})" : '';
    echo "📋 Subject: {$ban->subject->name} - Status: $status $reactivated\n";
    echo "   Ban ID: {$ban->id}\n";
    echo "   Ban Date: {$ban->banned_at}\n";
    echo "   Reason: {$ban->ban_reason}\n";
    echo "   Reactivated By: {$ban->reactivated_by}\n\n";
}

if ($bans->isEmpty()) {
    echo "❌ No formal bans found for ADEBANYO\n";
}

// Check security violations for ADEBANYO
echo "\n=== ADEBANYO VIOLATIONS ===\n";
$violations = ExamSecurityViolation::where('user_id', $user->id)
    ->with('subject')
    ->orderBy('occurred_at', 'desc')
    ->get();
    
foreach ($violations as $violation) {
    echo "🚨 {$violation->occurred_at}: {$violation->subject->name} - {$violation->violation_type}\n";
    echo "   Description: {$violation->description}\n";
    echo "   ID: {$violation->id}\n\n";
}

if ($violations->isEmpty()) {
    echo "❌ No violations found for ADEBANYO\n";
}

// Check specific violations with "NO MERCY" or "banned"
echo "\n=== SEARCHING FOR NO MERCY/BANNED VIOLATIONS ===\n";
$noMercyViolations = ExamSecurityViolation::where('user_id', $user->id)
    ->where(function($query) {
        $query->where('description', 'like', '%NO MERCY%')
              ->orWhere('description', 'like', '%banned%')
              ->orWhere('description', 'like', '%violation%')
              ->orWhere('description', 'like', '%tab_switch%');
    })
    ->with('subject')
    ->get();

foreach ($noMercyViolations as $violation) {
    echo "🔥 NO MERCY/BANNED: {$violation->occurred_at}: {$violation->subject->name}\n";
    echo "   Description: {$violation->description}\n";
    echo "   Type: {$violation->violation_type}\n\n";
}

if ($noMercyViolations->isEmpty()) {
    echo "❌ No NO MERCY/banned violations found for ADEBANYO\n";
}

// Test the current banned students query logic
echo "\n=== TESTING BANNED STUDENTS QUERY ===\n";

// Check if ADEBANYO appears in the combined query from the controller
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
        DB::raw("'FORMAL_BAN' as source_type")
    )
    ->where('b.is_active', true)
    ->whereNull('b.reactivated_at')
    ->where('u.id', $user->id);

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
        'v.description as ban_reason',
        'v.occurred_at as banned_at',
        DB::raw("'NO_MERCY_VIOLATION' as source_type")
    )
    ->where('v.description', 'like', '%NO MERCY%')
    ->where('u.id', $user->id)
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('exam_bans')
              ->whereColumn('exam_bans.user_id', 'v.user_id')
              ->whereColumn('exam_bans.subject_id', 'v.subject_id')
              ->where('exam_bans.is_active', true)
              ->whereNull('exam_bans.reactivated_at');
    });

echo "Active bans for ADEBANYO:\n";
$activeBans = $activeBansQuery->get();
foreach ($activeBans as $ban) {
    echo "- FORMAL_BAN: {$ban->subject_name} (Ban ID: {$ban->ban_id})\n";
}

echo "\nNO MERCY violations for ADEBANYO:\n";
$noMercyResults = $noMercyQuery->get();
foreach ($noMercyResults as $violation) {
    echo "- NO_MERCY: {$violation->subject_name} (Violation ID: {$violation->ban_id})\n";
}

echo "\n=== CONCLUSION ===\n";
if ($activeBans->isEmpty() && $noMercyResults->isEmpty()) {
    echo "❌ ADEBANYO should NOT appear in banned students list\n";
} else {
    echo "✅ ADEBANYO should appear in banned students list\n";
    echo "   Active formal bans: " . count($activeBans) . "\n";
    echo "   NO MERCY violations: " . count($noMercyResults) . "\n";
}