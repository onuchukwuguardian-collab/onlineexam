<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSession;

echo "🔍 INVESTIGATING CHIDINMA EZE'S BAN STATUS\n";
echo "=========================================\n\n";

// Find Chidinma Eze
$student = User::where('name', 'like', '%Chidinma%')->first();
if (!$student) {
    echo "❌ Chidinma Eze not found\n";
    exit(1);
}

echo "👩‍🎓 Student: {$student->name} (ID: {$student->id})\n";
echo "📧 Email: {$student->email}\n";
echo "📝 Registration: {$student->registration_number}\n\n";

// Find Mathematics subject
$mathSubject = Subject::where('name', 'like', '%Mathematics%')->first();
if (!$mathSubject) {
    echo "❌ Mathematics subject not found\n";
    exit(1);
}

echo "📚 Subject: {$mathSubject->name} (ID: {$mathSubject->id})\n\n";

// Check ALL violations for this student in Mathematics
echo "🔍 ALL VIOLATIONS FOR CHIDINMA IN MATHEMATICS:\n";
echo "==============================================\n";

$allViolations = DB::table('exam_security_violations')
    ->where('user_id', $student->id)
    ->where('subject_id', $mathSubject->id)
    ->orderBy('occurred_at', 'desc')
    ->get();

if ($allViolations->count() === 0) {
    echo "✅ No violations found - ban should not exist\n";
} else {
    echo "Found {$allViolations->count()} violation(s):\n\n";
    
    foreach ($allViolations as $index => $violation) {
        echo "Violation #" . ($index + 1) . ":\n";
        echo "  📅 Date: {$violation->occurred_at}\n";
        echo "  🏷️  Type: {$violation->violation_type}\n";
        echo "  📝 Description: {$violation->description}\n";
        echo "  🌐 IP: {$violation->ip_address}\n";
        echo "  🖥️  User Agent: " . substr($violation->user_agent, 0, 50) . "...\n";
        
        if ($violation->metadata) {
            $metadata = json_decode($violation->metadata, true);
            if (isset($metadata['test_violation']) && $metadata['test_violation']) {
                echo "  🧪 TEST VIOLATION: This was created by our test script\n";
            }
            if (isset($metadata['policy'])) {
                echo "  📋 Policy: {$metadata['policy']}\n";
            }
        }
        echo "\n";
    }
}

// Check active exam sessions for this student in Mathematics
echo "📚 EXAM SESSIONS FOR CHIDINMA IN MATHEMATICS:\n";
echo "============================================\n";

$examSessions = ExamSession::where('user_id', $student->id)
    ->where('subject_id', $mathSubject->id)
    ->orderBy('created_at', 'desc')
    ->get();

if ($examSessions->count() === 0) {
    echo "✅ No exam sessions found\n";
} else {
    echo "Found {$examSessions->count()} exam session(s):\n\n";
    
    foreach ($examSessions as $index => $session) {
        echo "Session #" . ($index + 1) . " (ID: {$session->id}):\n";
        echo "  📅 Created: {$session->created_at}\n";
        echo "  🟢 Started: " . ($session->started_at ?? 'Not started') . "\n";
        echo "  🔴 Expires: " . ($session->expires_at ?? 'No expiry') . "\n";
        echo "  ✅ Active: " . ($session->is_active ? 'YES' : 'NO') . "\n";
        echo "  ⏱️  Duration: {$session->duration_minutes} minutes\n";
        echo "  📍 Current Question: {$session->current_question_index}\n";
        echo "\n";
    }
}

// Check if there are recent legitimate violations vs test violations
echo "🕵️ ANALYSIS:\n";
echo "============\n";

$testViolations = $allViolations->filter(function($v) {
    $metadata = json_decode($v->metadata, true);
    return isset($metadata['test_violation']) && $metadata['test_violation'];
});

$realViolations = $allViolations->filter(function($v) {
    $metadata = json_decode($v->metadata, true);
    return !isset($metadata['test_violation']) || !$metadata['test_violation'];
});

echo "🧪 Test violations (from our scripts): {$testViolations->count()}\n";
echo "🚨 Real violations (from actual activity): {$realViolations->count()}\n\n";

if ($realViolations->count() > 0) {
    echo "⚠️  REAL VIOLATIONS FOUND:\n";
    foreach ($realViolations as $violation) {
        echo "  - {$violation->occurred_at}: {$violation->violation_type} - {$violation->description}\n";
    }
    echo "\n🔍 This suggests Chidinma DID have real violations during active exam sessions.\n";
} else {
    echo "✅ NO REAL VIOLATIONS - only test data\n";
    echo "🔧 The ban is likely from our test script and should be cleaned up.\n";
}

// Check reactivation status
echo "\n🔄 REACTIVATION HISTORY:\n";
echo "=======================\n";

$reactivations = DB::table('exam_security_violations')
    ->where('user_id', $student->id)
    ->where('subject_id', $mathSubject->id)
    ->whereIn('violation_type', ['admin_reactivation', 'admin_bulk_reactivation'])
    ->orderBy('occurred_at', 'desc')
    ->get();

if ($reactivations->count() === 0) {
    echo "No reactivation records\n";
} else {
    echo "Found {$reactivations->count()} reactivation(s):\n";
    foreach ($reactivations as $reactivation) {
        echo "  📅 {$reactivation->occurred_at}: {$reactivation->description}\n";
    }
}

// Final recommendation
echo "\n💡 RECOMMENDATION:\n";
echo "==================\n";

if ($realViolations->count() === 0 && $testViolations->count() > 0) {
    echo "🧹 CLEAN UP NEEDED: This ban appears to be from test data only.\n";
    echo "   The student should be reactivated or test data should be removed.\n";
    echo "   Run this to clean up test violations:\n";
    echo "   DELETE FROM exam_security_violations WHERE user_id = {$student->id} AND subject_id = {$mathSubject->id} AND JSON_EXTRACT(metadata, '$.test_violation') = true;\n";
} elseif ($realViolations->count() > 0) {
    echo "✅ LEGITIMATE BAN: The student has real violations and should remain banned.\n";
    echo "   Latest real violation: {$realViolations->first()->occurred_at}\n";
} else {
    echo "❓ UNCLEAR: No violations found but student appears banned.\n";
    echo "   This might be a data inconsistency issue.\n";
}

echo "\n🎯 Admin dashboard: http://localhost:8000/admin/security/banned-students\n";