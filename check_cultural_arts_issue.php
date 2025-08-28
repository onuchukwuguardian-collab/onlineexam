<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;
use App\Models\Subject;

echo "=== CULTURAL CREATIVE ARTS INVESTIGATION ===\n\n";

$user = User::where('email', 'john.ade@example.com')->first();
$culturalArts = Subject::where('name', 'like', '%Cultural%')->first();

echo "User: {$user->name} (ID: {$user->id})\n";
echo "Subject: {$culturalArts->name} (ID: {$culturalArts->id})\n\n";

// Check Cultural Creative Arts violations
echo "=== CULTURAL CREATIVE ARTS VIOLATIONS ===\n";
$culturalViolations = ExamSecurityViolation::where('user_id', $user->id)
    ->where('subject_id', $culturalArts->id)
    ->orderBy('occurred_at', 'desc')
    ->get();

$violationCount = 0;
foreach ($culturalViolations as $violation) {
    $violationCount++;
    echo "{$violationCount}. {$violation->occurred_at}: {$violation->violation_type}\n";
    echo "   Description: {$violation->description}\n";
    echo "   ID: {$violation->id}\n\n";
}

echo "Total violations for Cultural Creative Arts: {$violationCount}\n\n";

// Check if there should be a formal ban
if ($violationCount >= 3) {
    echo "❌ ISSUE: Student has {$violationCount} violations but no formal ban!\n";
    
    // Check if there's a formal ban
    $culturalBan = ExamBan::where('user_id', $user->id)
        ->where('subject_id', $culturalArts->id)
        ->first();
    
    if ($culturalBan) {
        echo "✅ Formal ban exists: {$culturalBan->banned_at} (Active: " . ($culturalBan->is_active ? 'Yes' : 'No') . ")\n";
    } else {
        echo "❌ NO FORMAL BAN FOUND!\n";
        echo "🔧 This explains why student can't start exam - violations exist but no proper ban record.\n";
    }
} else {
    echo "ℹ️ Student has {$violationCount} violations (less than 3, no ban expected)\n";
}

// Check the banned students query specifically for Cultural Creative Arts
echo "\n=== TESTING BANNED STUDENTS QUERY FOR CULTURAL ARTS ===\n";

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
    ->where('s.id', $culturalArts->id)
    ->whereNotExists(function($query) {
        $query->select(DB::raw(1))
              ->from('exam_bans')
              ->whereColumn('exam_bans.user_id', 'v.user_id')
              ->whereColumn('exam_bans.subject_id', 'v.subject_id')
              ->where('exam_bans.is_active', true)
              ->whereNull('exam_bans.reactivated_at');
    });

$noMercyResults = $noMercyQuery->get();
echo "NO MERCY violations found in query: " . count($noMercyResults) . "\n";

foreach ($noMercyResults as $violation) {
    echo "- {$violation->subject_name}: {$violation->ban_reason}\n";
}

if (count($noMercyResults) == 0) {
    echo "❌ PROBLEM: Cultural Creative Arts violations not appearing in banned students query!\n";
    echo "💡 This is because the violations don't contain 'NO MERCY' text.\n";
    
    // Check what the actual violation descriptions contain
    echo "\nActual violation descriptions for Cultural Creative Arts:\n";
    foreach ($culturalViolations as $violation) {
        $hasNoMercy = str_contains($violation->description, 'NO MERCY');
        $hasBanned = str_contains($violation->description, 'banned');
        $hasViolation = str_contains($violation->description, 'violation');
        
        echo "- '{$violation->description}'\n";
        echo "  Contains 'NO MERCY': " . ($hasNoMercy ? 'Yes' : 'No') . "\n";
        echo "  Contains 'banned': " . ($hasBanned ? 'Yes' : 'No') . "\n";
        echo "  Contains 'violation': " . ($hasViolation ? 'Yes' : 'No') . "\n\n";
    }
}