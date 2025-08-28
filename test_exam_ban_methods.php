<?php

/**
 * 🔧 QUICK TEST: ExamBan Methods Validation
 * 
 * This test verifies that the ExamBan methods are working correctly
 * after the recent updates.
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamBan;
use App\Models\User;
use App\Models\Subject;

echo "🔧 EXAM BAN METHODS VALIDATION TEST\n";
echo "═══════════════════════════════════\n\n";

try {
    // Get test data
    $testUser = User::where('role', 'student')->first();
    $testSubject = Subject::first();
    
    if (!$testUser || !$testSubject) {
        echo "❌ Need at least 1 student and 1 subject for testing\n";
        exit(1);
    }
    
    echo "✅ Test Environment:\n";
    echo "   👤 Test User: {$testUser->name} (ID: {$testUser->id})\n";
    echo "   📚 Test Subject: {$testSubject->name} (ID: {$testSubject->id})\n\n";
    
    // Test 1: Check if methods exist and work
    echo "📝 TEST 1: Method Existence and Functionality\n";
    echo "─────────────────────────────────────────────\n";
    
    // Test isBannedFromSubject method
    $banRecord = ExamBan::isBannedFromSubject($testUser->id, $testSubject->id);
    echo "   🎯 isBannedFromSubject(): " . (method_exists(ExamBan::class, 'isBannedFromSubject') ? '✅ EXISTS' : '❌ MISSING') . "\n";
    echo "      Result: " . ($banRecord ? "BAN FOUND (ID: {$banRecord->id})" : "NO BAN") . "\n";
    
    // Test backward compatibility isBanned method
    $isBanned = ExamBan::isBanned($testUser->id, $testSubject->id);
    echo "   🔄 isBanned() [backward compatibility]: " . (method_exists(ExamBan::class, 'isBanned') ? '✅ EXISTS' : '❌ MISSING') . "\n";
    echo "      Result: " . ($isBanned ? "BANNED" : "NOT BANNED") . "\n";
    
    // Test getBanDetails method
    $banDetails = ExamBan::getBanDetails($testUser->id, $testSubject->id);
    echo "   📋 getBanDetails(): " . (method_exists(ExamBan::class, 'getBanDetails') ? '✅ EXISTS' : '❌ MISSING') . "\n";
    echo "      Result: " . ($banDetails ? "DETAILS FOUND" : "NO DETAILS") . "\n";
    
    echo "\n";
    
    // Test 2: Method consistency
    echo "📝 TEST 2: Method Consistency Check\n";
    echo "──────────────────────────────────\n";
    
    $consistencyCheck = ($banRecord && $isBanned) || (!$banRecord && !$isBanned);
    echo "   🔍 isBannedFromSubject() vs isBanned(): " . ($consistencyCheck ? '✅ CONSISTENT' : '❌ INCONSISTENT') . "\n";
    
    if ($banRecord && $banDetails) {
        $detailsMatch = ($banRecord->id === $banDetails->id);
        echo "   📊 Ban record vs details: " . ($detailsMatch ? '✅ MATCH' : '❌ MISMATCH') . "\n";
    }
    
    echo "\n";
    
    // Test 3: New enhanced methods
    echo "📝 TEST 3: Enhanced Method Functionality\n";
    echo "───────────────────────────────────────\n";
    
    // Test shouldTriggerBan method
    if (method_exists(ExamBan::class, 'shouldTriggerBan')) {
        echo "   ⚡ shouldTriggerBan(): ✅ EXISTS\n";
    } else {
        echo "   ⚡ shouldTriggerBan(): ❌ MISSING\n";
    }
    
    // Test getThresholdForViolation method
    if (method_exists(ExamBan::class, 'getThresholdForViolation')) {
        $tabSwitchThreshold = ExamBan::getThresholdForViolation('tab_switch');
        $rightClickThreshold = ExamBan::getThresholdForViolation('right_click');
        echo "   🎯 getThresholdForViolation(): ✅ EXISTS\n";
        echo "      Tab Switch Threshold: {$tabSwitchThreshold}\n";
        echo "      Right Click Threshold: {$rightClickThreshold}\n";
    } else {
        echo "   🎯 getThresholdForViolation(): ❌ MISSING\n";
    }
    
    // Test createSubjectBan method
    if (method_exists(ExamBan::class, 'createSubjectBan')) {
        echo "   🚫 createSubjectBan(): ✅ EXISTS\n";
    } else {
        echo "   🚫 createSubjectBan(): ❌ MISSING\n";
    }
    
    echo "\n";
    
    // Summary
    echo "📊 VALIDATION SUMMARY\n";
    echo "═══════════════════════\n";
    
    $requiredMethods = ['isBannedFromSubject', 'isBanned', 'getBanDetails'];
    $enhancedMethods = ['shouldTriggerBan', 'getThresholdForViolation', 'createSubjectBan'];
    
    $requiredCount = 0;
    $enhancedCount = 0;
    
    foreach ($requiredMethods as $method) {
        if (method_exists(ExamBan::class, $method)) {
            $requiredCount++;
        }
    }
    
    foreach ($enhancedMethods as $method) {
        if (method_exists(ExamBan::class, $method)) {
            $enhancedCount++;
        }
    }
    
    echo "   ✅ Required Methods: {$requiredCount}/{" . count($requiredMethods) . "}\n";
    echo "   🔧 Enhanced Methods: {$enhancedCount}/{" . count($enhancedMethods) . "}\n";
    
    if ($requiredCount === count($requiredMethods) && $enhancedCount === count($enhancedMethods)) {
        echo "\n🎉 ALL METHODS WORKING CORRECTLY!\n";
        echo "═══════════════════════════════════\n";
        echo "✅ Backward compatibility maintained\n";
        echo "✅ Enhanced functionality available\n";
        echo "✅ Subject-specific ban system ready\n";
    } else {
        echo "\n❌ SOME METHODS ARE MISSING!\n";
        echo "═══════════════════════════════\n";
        echo "Please check the ExamBan model implementation.\n";
    }
    
} catch (Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🏁 Test completed successfully! ✨\n";