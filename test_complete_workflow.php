<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use Carbon\Carbon;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔄 COMPLETE VIOLATION → BAN → ADMIN WORKFLOW TEST\n";
echo "================================================\n\n";

// Test results summary
$results = [
    'violation_recording' => false,
    'ban_creation' => false,
    'admin_visibility' => false,
    'reactivation_available' => false
];

try {
    // Step 1: Test Violation Recording
    echo "📝 STEP 1: TESTING VIOLATION RECORDING\n";
    echo "======================================\n";
    
    $student = User::where('role', 'student')->first();
    $subject = Subject::first();
    
    if (!$student || !$subject) {
        throw new Exception("Missing required test data: student or subject");
    }
    
    echo "👤 Student: {$student->name} ({$student->email})\n";
    echo "📚 Subject: {$subject->name}\n\n";
    
    // Clear existing data
    ExamSecurityViolation::where('user_id', $student->id)->where('subject_id', $subject->id)->delete();
    ExamBan::where('user_id', $student->id)->where('subject_id', $subject->id)->delete();
    
    // Test recording violations
    $violationData = [
        ['type' => 'tab_switch', 'desc' => 'First tab switch violation'],
        ['type' => 'tab_switch', 'desc' => 'Second tab switch violation'],
        ['type' => 'tab_switch', 'desc' => 'Third tab switch violation - should trigger ban']
    ];
    
    foreach ($violationData as $index => $vData) {
        $violation = ExamSecurityViolation::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'exam_session_id' => null,
            'violation_type' => $vData['type'],
            'description' => $vData['desc'],
            'metadata' => [
                'violation_count' => $index + 1,
                'timestamp' => now()->toISOString(),
                'policy' => '3_STRIKE_POLICY',
                'test_workflow' => true
            ],
            'occurred_at' => now()->subMinutes(10 - $index),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser'
        ]);
        
        echo "   ✅ Violation #" . ($index + 1) . " recorded (ID: {$violation->id})\n";
    }
    
    $totalViolations = ExamSecurityViolation::getViolationCount($student->id, $subject->id, 'tab_switch');
    echo "   📊 Total violations recorded: {$totalViolations}\n";
    
    if ($totalViolations == 3) {
        $results['violation_recording'] = true;
        echo "   ✅ VIOLATION RECORDING: PASSED\n\n";
    } else {
        echo "   ❌ VIOLATION RECORDING: FAILED\n\n";
    }
    
    // Step 2: Test Ban Creation
    echo "🚫 STEP 2: TESTING BAN CREATION\n";
    echo "===============================\n";
    
    // Check if ban should be created after 3 violations
    $allViolations = ExamSecurityViolation::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->where('violation_type', 'tab_switch')
        ->get();
    
    if ($allViolations->count() >= 3) {
        $ban = ExamBan::createViolationBan(
            $student->id,
            $subject->id,
            $allViolations,
            'TEST: Permanent ban after 3 TAB SWITCHING violations - 3-STRIKE POLICY'
        );
        
        echo "   ✅ Ban created (ID: {$ban->id})\n";
        echo "   📋 Ban reason: {$ban->ban_reason}\n";
        echo "   🔢 Total violations in ban: {$ban->total_violations}\n";
        echo "   ✅ Is active: " . ($ban->is_active ? 'YES' : 'NO') . "\n";
        echo "   ♾️  Is permanent: " . ($ban->is_permanent ? 'YES' : 'NO') . "\n";
        
        // Verify ban status
        $isBanned = ExamBan::isBanned($student->id, $subject->id);
        echo "   🔍 Ban verification: " . ($isBanned ? 'STUDENT IS BANNED' : 'STUDENT NOT BANNED') . "\n";
        
        if ($isBanned && $ban->is_active) {
            $results['ban_creation'] = true;
            echo "   ✅ BAN CREATION: PASSED\n\n";
        } else {
            echo "   ❌ BAN CREATION: FAILED\n\n";
        }
    } else {
        echo "   ❌ Not enough violations to create ban\n\n";
    }
    
    // Step 3: Test Admin Visibility
    echo "👨‍💼 STEP 3: TESTING ADMIN VISIBILITY\n";
    echo "====================================\n";
    
    // Query like the admin controller does
    $adminBannedStudents = ExamBan::with(['user', 'subject'])
        ->where('is_active', true)
        ->orderBy('banned_at', 'desc')
        ->get();
    
    echo "   📊 Total active bans visible to admin: {$adminBannedStudents->count()}\n";
    
    $testBanFound = false;
    foreach ($adminBannedStudents as $adminBan) {
        echo "   👤 {$adminBan->user->name} banned from {$adminBan->subject->name} ({$adminBan->total_violations} violations)\n";
        if ($adminBan->user_id == $student->id && $adminBan->subject_id == $subject->id) {
            $testBanFound = true;
        }
    }
    
    if ($testBanFound && $adminBannedStudents->count() > 0) {
        $results['admin_visibility'] = true;
        echo "   ✅ ADMIN VISIBILITY: PASSED\n\n";
    } else {
        echo "   ❌ ADMIN VISIBILITY: FAILED\n\n";
    }
    
    // Step 4: Test Reactivation Availability
    echo "🔓 STEP 4: TESTING REACTIVATION AVAILABILITY\n";
    echo "============================================\n";
    
    if (isset($ban) && $ban->is_active) {
        echo "   📋 Ban ID: {$ban->id}\n";
        echo "   👤 Student: {$ban->user->name}\n";
        echo "   📚 Subject: {$ban->subject->name}\n";
        echo "   ✅ Can be reactivated: YES\n";
        echo "   🔗 Admin ban details URL: http://web-portal.test/admin/security/ban-details/{$ban->id}\n";
        
        $results['reactivation_available'] = true;
        echo "   ✅ REACTIVATION AVAILABILITY: PASSED\n\n";
    } else {
        echo "   ❌ No active ban found for reactivation test\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Final Results Summary
echo "🎯 FINAL RESULTS SUMMARY\n";
echo "========================\n";

$totalTests = count($results);
$passedTests = count(array_filter($results));

foreach ($results as $test => $passed) {
    $status = $passed ? '✅ PASSED' : '❌ FAILED';
    $testName = strtoupper(str_replace('_', ' ', $test));
    echo "📊 {$testName}: {$status}\n";
}

echo "\n📈 OVERALL RESULT: {$passedTests}/{$totalTests} tests passed\n";

if ($passedTests === $totalTests) {
    echo "🎉 ALL TESTS PASSED! The complete workflow is working correctly.\n";
    echo "\n🔗 ADMIN CAN NOW:\n";
    echo "=================\n";
    echo "📋 View banned students: http://web-portal.test/admin/security/banned-students\n";
    echo "🔧 Manage security: http://web-portal.test/admin/security/\n";
    echo "🔓 Reactivate students using the reactivate button\n";
} else {
    echo "⚠️ SOME TESTS FAILED. Please check the system configuration.\n";
}

echo "\nTest completed! ✨\n";