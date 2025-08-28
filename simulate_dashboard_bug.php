<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ExamBan;
use App\Models\Subject;
use App\Models\User;
use App\Models\ExamSecurityViolation;
use Illuminate\Support\Facades\DB;

echo "🔍 SIMULATING DASHBOARD VISIT BUG\n";
echo "=================================\n\n";

// Get a student who hasn't violated anything
$innocentStudent = User::where('role', 'student')
    ->whereNotIn('id', function($query) {
        $query->select('user_id')
              ->from('exam_security_violations');
    })
    ->first();

if (!$innocentStudent) {
    echo "❌ No innocent students found!\n";
    
    // Let's just pick any student
    $innocentStudent = User::where('role', 'student')->first();
    echo "Using student: {$innocentStudent->name} (may have violations)\n\n";
} else {
    echo "✅ Found innocent student: {$innocentStudent->name} (no violations)\n\n";
}

$basicScience = Subject::where('name', 'like', '%Basic Science%')->first();

echo "📊 BEFORE DASHBOARD VISIT:\n";
echo "Active bans for Basic Science: " . ExamBan::where('subject_id', $basicScience->id)->where('is_active', true)->count() . "\n\n";

echo "🚨 SIMULATING createMissingBans() for user {$innocentStudent->id}:\n";
echo "=======================================================\n\n";

// This is the EXACT logic from UserDashboardController->createMissingBans()
try {
    DB::beginTransaction();
    
    $userId = $innocentStudent->id;
    
    echo "1. Checking for tab switch violations...\n";
    
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
        
    echo "   Found {$tabSwitchViolations->count()} subjects with tab switch violations for this user\n";
    
    foreach ($tabSwitchViolations as $violation) {
        $subject = Subject::find($violation->subject_id);
        echo "   - Subject: {$subject->name} ({$violation->violation_count} violations)\n";
    }
    
    echo "\n2. Checking for right-click violations...\n";
    
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
        
    echo "   Found {$rightClickViolations->count()} subjects with 15+ right-click violations for this user\n";
    
    foreach ($rightClickViolations as $violation) {
        $subject = Subject::find($violation->subject_id);
        echo "   - Subject: {$subject->name} ({$violation->violation_count} violations)\n";
    }
    
    $totalNewBans = $tabSwitchViolations->count() + $rightClickViolations->count();
    
    if ($totalNewBans === 0) {
        echo "\n✅ No new bans would be created - dashboard logic is working correctly!\n";
    } else {
        echo "\n⚠️ This would create {$totalNewBans} new ban records!\n";
        echo "This explains why students appear banned when they visit the dashboard.\n";
    }
    
    DB::rollBack(); // Don't actually create the bans
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n📊 AFTER SIMULATION:\n";
echo "Active bans for Basic Science: " . ExamBan::where('subject_id', $basicScience->id)->where('is_active', true)->count() . "\n\n";

echo "🎯 CONCLUSION:\n";
echo "=============\n";
echo "The createMissingBans() method is called every time a student visits their dashboard.\n";
echo "If it finds violations that don't have corresponding ban records, it creates them.\n";
echo "This might be creating bans for violations that occurred in test scenarios.\n\n";

echo "✅ Simulation complete - no actual bans were created!\n";