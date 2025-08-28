<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ExamBan;
use App\Models\Subject;
use App\Models\User;
use App\Models\ExamSecurityViolation;
use Illuminate\Support\Facades\DB;

echo "🚨 DASHBOARD BAN CREATION BUG INVESTIGATION\n";
echo "==========================================\n\n";

// Find Basic Science subject
$basicScience = Subject::where('name', 'like', '%Basic Science%')->first();

if (!$basicScience) {
    echo "❌ Basic Science subject not found!\n";
    exit;
}

echo "📚 Subject: {$basicScience->name} (ID: {$basicScience->id})\n\n";

// Check total violations for Basic Science
$totalViolations = ExamSecurityViolation::where('subject_id', $basicScience->id)->count();
echo "📊 Total violations for Basic Science: {$totalViolations}\n\n";

if ($totalViolations === 0) {
    echo "✅ No violations found - this is not the issue.\n";
    exit;
}

// Check violations by type
$tabSwitchViolations = ExamSecurityViolation::where('subject_id', $basicScience->id)
    ->where('violation_type', 'tab_switch')
    ->count();

$rightClickViolations = ExamSecurityViolation::where('subject_id', $basicScience->id)
    ->where('violation_type', 'right_click')
    ->count();

echo "🔍 Violation breakdown:\n";
echo "- Tab switch violations: {$tabSwitchViolations}\n";
echo "- Right-click violations: {$rightClickViolations}\n\n";

// Get unique violators
$uniqueViolators = ExamSecurityViolation::where('subject_id', $basicScience->id)
    ->distinct('user_id')
    ->count();

echo "👥 Unique students who violated in Basic Science: {$uniqueViolators}\n\n";

// Check the problematic query from createMissingBans
echo "🔍 TESTING THE DASHBOARD BAN CREATION QUERY:\n";
echo "===========================================\n\n";

echo "Testing tab switch violations query (IMMEDIATE BAN):\n";

// This is the EXACT query from createMissingBans - but I'll test it per user
$allStudents = User::where('role', 'student')->get();

foreach ($allStudents as $student) {
    // Test the tab switch query for this student
    $tabSwitchForStudent = DB::table('exam_security_violations as v')
        ->select('v.subject_id', DB::raw('COUNT(*) as violation_count'), DB::raw('MAX(v.occurred_at) as latest_violation'))
        ->where('v.user_id', $student->id)
        ->where('v.violation_type', 'tab_switch')
        ->where('v.subject_id', $basicScience->id) // Only check Basic Science
        ->whereNotExists(function($query) use ($student) {
            $query->select(DB::raw(1))
                  ->from('exam_bans')
                  ->where('exam_bans.user_id', $student->id)
                  ->whereColumn('exam_bans.subject_id', 'v.subject_id')
                  ->where('exam_bans.is_active', true);
        })
        ->groupBy('v.subject_id')
        ->get();

    if ($tabSwitchForStudent->count() > 0) {
        echo "⚠️ {$student->name} would get banned for Basic Science (tab switch)\n";
        echo "   Violations: {$tabSwitchForStudent->first()->violation_count}\n";
        
        // Check if this student actually violated in Basic Science
        $actualViolations = ExamSecurityViolation::where('user_id', $student->id)
            ->where('subject_id', $basicScience->id)
            ->where('violation_type', 'tab_switch')
            ->count();
            
        echo "   Actual violations by this student: {$actualViolations}\n\n";
        
        if ($actualViolations === 0) {
            echo "🚨 BUG CONFIRMED: Student would be banned despite having NO violations!\n\n";
        }
    }
}

echo "✅ Investigation complete!\n";