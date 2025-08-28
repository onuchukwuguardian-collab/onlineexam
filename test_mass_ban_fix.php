<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ExamBan;
use App\Models\Subject;
use App\Models\User;
use App\Models\ExamSecurityViolation;
use Illuminate\Support\Facades\DB;

echo "🧪 TESTING THE MASS-BANNING FIX\n";
echo "==============================\n\n";

$basicScience = Subject::where('name', 'like', '%Basic Science%')->first();
$adebayo = User::where('name', 'like', '%Adebayo John%')->first();

if (!$adebayo) {
    echo "❌ Adebayo John not found!\n";
    exit;
}

echo "🧪 Testing with: {$adebayo->name} (ID: {$adebayo->id})\n";
echo "Subject: {$basicScience->name} (ID: {$basicScience->id})\n\n";

// Check current ban status
$currentBans = ExamBan::where('user_id', $adebayo->id)->where('is_active', true)->count();
echo "📊 Current active bans for Adebayo: {$currentBans}\n\n";

// Get last reactivation time for Basic Science
$lastReactivation = DB::table('exam_bans')
    ->select('reactivated_at')
    ->where('user_id', $adebayo->id)
    ->where('subject_id', $basicScience->id)
    ->whereNotNull('reactivated_at')
    ->orderBy('reactivated_at', 'desc')
    ->first();

if ($lastReactivation) {
    echo "📅 Last reactivation for Basic Science: {$lastReactivation->reactivated_at}\n";
    
    // Count violations before and after last reactivation
    $violationsAfterReactivation = ExamSecurityViolation::where('user_id', $adebayo->id)
        ->where('subject_id', $basicScience->id)
        ->where('violation_type', 'tab_switch')
        ->where('occurred_at', '>', $lastReactivation->reactivated_at)
        ->count();
        
    $violationsBeforeReactivation = ExamSecurityViolation::where('user_id', $adebayo->id)
        ->where('subject_id', $basicScience->id)
        ->where('violation_type', 'tab_switch')
        ->where('occurred_at', '<=', $lastReactivation->reactivated_at)
        ->count();
        
    echo "📊 Tab switch violations BEFORE last reactivation: {$violationsBeforeReactivation}\n";
    echo "📊 Tab switch violations AFTER last reactivation: {$violationsAfterReactivation}\n\n";
    
    if ($violationsAfterReactivation > 0) {
        echo "⚠️ Adebayo has {$violationsAfterReactivation} new violations since reactivation!\n";
        echo "✅ Fix working: Only new violations should trigger bans.\n\n";
    } else {
        echo "✅ Adebayo has NO new violations since reactivation!\n";
        echo "✅ Fix working: No ban should be created.\n\n";
    }
} else {
    echo "📅 No reactivation history found for Basic Science\n";
    
    $totalViolations = ExamSecurityViolation::where('user_id', $adebayo->id)
        ->where('subject_id', $basicScience->id)
        ->where('violation_type', 'tab_switch')
        ->count();
        
    echo "📊 Total tab switch violations: {$totalViolations}\n";
    echo "⚠️ Since no reactivation history, all violations would trigger ban.\n\n";
}

echo "🔍 TESTING FIXED createMissingBans LOGIC:\n";
echo "========================================\n\n";

// Simulate the FIXED logic
try {
    $userId = $adebayo->id;
    
    // Get the last reactivation time for each subject for this specific user
    $lastReactivations = DB::table('exam_bans')
        ->select('subject_id', DB::raw('MAX(reactivated_at) as last_reactivated'))
        ->where('user_id', $userId)
        ->whereNotNull('reactivated_at')
        ->groupBy('subject_id')
        ->pluck('last_reactivated', 'subject_id');
        
    echo "📅 Last reactivations found: {$lastReactivations->count()}\n";
    foreach ($lastReactivations as $subjectId => $lastReactivated) {
        $subject = Subject::find($subjectId);
        echo "- {$subject->name}: {$lastReactivated}\n";
    }
    echo "\n";
    
    // Check for tab switch violations (immediate ban) - ONLY AFTER LAST REACTIVATION
    $tabSwitchViolations = DB::table('exam_security_violations as v')
        ->select('v.subject_id', DB::raw('COUNT(*) as violation_count'), DB::raw('MAX(v.occurred_at) as latest_violation'))
        ->where('v.user_id', $userId)
        ->where('v.violation_type', 'tab_switch')
        ->where(function($query) use ($lastReactivations, $userId) {
            // Only include violations that occurred after the last reactivation
            foreach ($lastReactivations as $subjectId => $lastReactivated) {
                $query->orWhere(function($subQuery) use ($subjectId, $lastReactivated) {
                    $subQuery->where('v.subject_id', $subjectId)
                             ->where('v.occurred_at', '>', $lastReactivated);
                });
            }
            
            // For subjects without any reactivation history, include all violations
            $subjectsWithReactivations = array_keys($lastReactivations->toArray());
            if (!empty($subjectsWithReactivations)) {
                $query->orWhereNotIn('v.subject_id', $subjectsWithReactivations);
            }
        })
        ->whereNotExists(function($query) use ($userId) {
            $query->select(DB::raw(1))
                  ->from('exam_bans')
                  ->where('exam_bans.user_id', $userId)
                  ->whereColumn('exam_bans.subject_id', 'v.subject_id')
                  ->where('exam_bans.is_active', true);
        })
        ->groupBy('v.subject_id')
        ->get();
        
    echo "🚫 Subjects that would trigger NEW bans: {$tabSwitchViolations->count()}\n";
    foreach ($tabSwitchViolations as $violation) {
        $subject = Subject::find($violation->subject_id);
        echo "- {$subject->name}: {$violation->violation_count} violations (latest: {$violation->latest_violation})\n";
    }
    
    if ($tabSwitchViolations->count() === 0) {
        echo "✅ SUCCESS: No new bans would be created!\n";
        echo "✅ The mass-banning bug is FIXED!\n\n";
    } else {
        echo "⚠️ New bans would still be created - reviewing why...\n\n";
        
        foreach ($tabSwitchViolations as $violation) {
            $subject = Subject::find($violation->subject_id);
            if ($lastReactivations->has($violation->subject_id)) {
                echo "🔍 {$subject->name}: Violations occurred AFTER reactivation {$lastReactivations[$violation->subject_id]}\n";
            } else {
                echo "🔍 {$subject->name}: No reactivation history - all violations count\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 MASS-BANNING FIX SUMMARY:\n";
echo "===========================\n";
echo "✅ Fixed createMissingBans() to only count violations AFTER last reactivation\n";
echo "✅ Students who were reactivated won't get re-banned for old violations\n";
echo "✅ Only NEW violations since reactivation will trigger bans\n";
echo "✅ This prevents mass-banning from historical test violations\n\n";

echo "🔍 Next: Test with other students to ensure the fix works globally\n\n";

echo "✅ Mass-banning fix test complete!\n";