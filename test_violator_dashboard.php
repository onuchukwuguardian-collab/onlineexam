<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ExamBan;
use App\Models\Subject;
use App\Models\User;
use App\Models\ExamSecurityViolation;
use Illuminate\Support\Facades\DB;

echo "🔍 TESTING DASHBOARD WITH ACTUAL VIOLATORS\n";
echo "==========================================\n\n";

$basicScience = Subject::where('name', 'like', '%Basic Science%')->first();

// Get students who actually violated in Basic Science
$violators = User::whereIn('id', function($query) use ($basicScience) {
    $query->select('user_id')
          ->from('exam_security_violations')
          ->where('subject_id', $basicScience->id);
})->get();

echo "📊 Found {$violators->count()} students with violations in Basic Science:\n";
foreach ($violators as $violator) {
    $violationCount = ExamSecurityViolation::where('user_id', $violator->id)
        ->where('subject_id', $basicScience->id)
        ->count();
    echo "- {$violator->name}: {$violationCount} violations\n";
}
echo "\n";

// Test with the first violator
$testStudent = $violators->first();
if (!$testStudent) {
    echo "❌ No violators found!\n";
    exit;
}

echo "🧪 TESTING WITH: {$testStudent->name} (ID: {$testStudent->id})\n";
echo "===============================================\n\n";

// Check their violations
$violations = ExamSecurityViolation::where('user_id', $testStudent->id)
    ->where('subject_id', $basicScience->id)
    ->get();

echo "📋 This student's violations in Basic Science:\n";
foreach ($violations as $violation) {
    echo "- {$violation->violation_type}: {$violation->description} ({$violation->occurred_at})\n";
}
echo "\n";

echo "📊 BEFORE DASHBOARD VISIT:\n";
$existingBans = ExamBan::where('user_id', $testStudent->id)
    ->where('subject_id', $basicScience->id)
    ->where('is_active', true)
    ->count();
echo "Existing bans for this student in Basic Science: {$existingBans}\n\n";

echo "🚨 SIMULATING createMissingBans() for this student:\n";
echo "==================================================\n\n";

try {
    DB::beginTransaction();
    
    $userId = $testStudent->id;
    
    // Check for tab switch violations (immediate ban)
    $tabSwitchViolations = DB::table('exam_security_violations as v')
        ->select('v.subject_id', DB::raw('COUNT(*) as violation_count'), DB::raw('MAX(v.occurred_at) as latest_violation'))
        ->where('v.user_id', $userId)
        ->where('v.violation_type', 'tab_switch')
        ->whereNotExists(function($query) use ($userId) {
            $query->select(DB::raw(1))
                  ->from('exam_bans')
                  ->where('exam_bans.user_id', $userId)
                  ->whereColumn('exam_bans.subject_id', 'v.subject_id')
                  ->where('exam_bans.is_active', true);
        })
        ->groupBy('v.subject_id')
        ->get();
        
    echo "Tab switch subjects that would trigger bans: {$tabSwitchViolations->count()}\n";
    foreach ($tabSwitchViolations as $violation) {
        $subject = Subject::find($violation->subject_id);
        echo "- {$subject->name}: {$violation->violation_count} violations\n";
    }
    
    // Check for right-click violations (15+ strikes)
    $rightClickViolations = DB::table('exam_security_violations as v')
        ->select('v.subject_id', DB::raw('COUNT(*) as violation_count'), DB::raw('MAX(v.occurred_at) as latest_violation'))
        ->where('v.user_id', $userId)
        ->where('v.violation_type', 'right_click')
        ->whereNotExists(function($query) use ($userId) {
            $query->select(DB::raw(1))
                  ->from('exam_bans')
                  ->where('exam_bans.user_id', $userId)
                  ->whereColumn('exam_bans.subject_id', 'v.subject_id')
                  ->where('exam_bans.is_active', true);
        })
        ->groupBy('v.subject_id')
        ->havingRaw('COUNT(*) >= 15')
        ->get();
        
    echo "Right-click subjects that would trigger bans: {$rightClickViolations->count()}\n";
    foreach ($rightClickViolations as $violation) {
        $subject = Subject::find($violation->subject_id);
        echo "- {$subject->name}: {$violation->violation_count} violations\n";
    }
    
    $totalNewBans = $tabSwitchViolations->count() + $rightClickViolations->count();
    
    if ($totalNewBans > 0) {
        echo "\n⚠️ This student's dashboard visit would create {$totalNewBans} new ban(s)!\n";
        echo "THIS IS THE SOURCE OF THE MASS BANNING!\n";
        
        // Show which subjects would be affected
        foreach ($tabSwitchViolations as $violation) {
            $subject = Subject::find($violation->subject_id);
            echo "\n🚨 Would ban {$testStudent->name} from {$subject->name} (tab switch)\n";
        }
        foreach ($rightClickViolations as $violation) {
            $subject = Subject::find($violation->subject_id);
            echo "\n🚨 Would ban {$testStudent->name} from {$subject->name} (right-click)\n";
        }
    } else {
        echo "\n✅ No new bans would be created.\n";
    }
    
    DB::rollBack(); // Don't actually create the bans
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎯 MASS BANNING EXPLANATION:\n";
echo "===========================\n";
echo "1. Students with violations visit their dashboard\n";
echo "2. createMissingBans() runs automatically\n";
echo "3. It finds violations without corresponding ban records\n";
echo "4. It creates ban records for these violations\n";
echo "5. ALL STUDENTS then appear banned when viewing dashboards\n\n";

echo "💡 SOLUTION: The createMissingBans() logic needs to be reviewed.\n";
echo "It should only create bans for CURRENT violations, not historical/test ones.\n\n";

echo "✅ Investigation complete!\n";