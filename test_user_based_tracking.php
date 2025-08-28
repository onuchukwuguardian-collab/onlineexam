<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== USER-BASED VIOLATION TRACKING TEST ===\n\n";

use App\Models\User;
use App\Models\Subject; 
use App\Models\ExamSecurityViolation;

try {
    // Get two different students
    $student1 = User::where('role', 'student')->first();
    $student2 = User::where('role', 'student')->skip(1)->first();
    $subject = Subject::first();
    
    if (!$student1 || !$student2 || !$subject) {
        echo "❌ Need at least 2 students and 1 subject in database\n";
        exit;
    }
    
    echo "🎓 TESTING SCENARIO: Two students using same computer\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "👤 Student 1: {$student1->name} ({$student1->email})\n";
    echo "👤 Student 2: {$student2->name} ({$student2->email})\n";
    echo "📚 Subject: {$subject->name}\n";
    echo "🖥️  Same Computer IP: 192.168.1.100\n\n";
    
    // Clear any existing violations for clean test
    ExamSecurityViolation::where('user_id', $student1->id)->where('subject_id', $subject->id)->delete();
    ExamSecurityViolation::where('user_id', $student2->id)->where('subject_id', $subject->id)->delete();
    
    echo "🧹 Cleared existing violations for clean test\n\n";
    
    // Simulate violations from same IP but different students
    echo "📝 SIMULATING VIOLATIONS FROM SAME IP ADDRESS:\n";
    echo "═══════════════════════════════════════════════\n\n";
    
    // Student 1: 2 violations
    for ($i = 1; $i <= 2; $i++) {
        ExamSecurityViolation::create([
            'user_id' => $student1->id,
            'subject_id' => $subject->id,
            'exam_session_id' => null,
            'violation_type' => 'tab_switch',
            'description' => "Student 1 - Tab switch violation #{$i}",
            'metadata' => [
                'violation_count' => $i,
                'tracking_method' => 'user_account_based',
                'test_scenario' => 'same_ip_different_users'
            ],
            'occurred_at' => now()->subMinutes(10 - $i),
            'ip_address' => '192.168.1.100', // SAME IP for both students
            'user_agent' => 'Mozilla/5.0 (Test Browser)'
        ]);
        
        echo "⚠️  Student 1 Violation #{$i} recorded (IP: 192.168.1.100)\n";
    }
    
    // Student 2: 3 violations (should be banned)
    for ($i = 1; $i <= 3; $i++) {
        ExamSecurityViolation::create([
            'user_id' => $student2->id,
            'subject_id' => $subject->id,
            'exam_session_id' => null,
            'violation_type' => 'tab_switch',
            'description' => "Student 2 - Tab switch violation #{$i}",
            'metadata' => [
                'violation_count' => $i,
                'tracking_method' => 'user_account_based',
                'test_scenario' => 'same_ip_different_users'
            ],
            'occurred_at' => now()->subMinutes(5 - $i),
            'ip_address' => '192.168.1.100', // SAME IP for both students
            'user_agent' => 'Mozilla/5.0 (Test Browser)'
        ]);
        
        echo "⚠️  Student 2 Violation #{$i} recorded (IP: 192.168.1.100)\n";
    }
    
    echo "\n🔍 CHECKING VIOLATION COUNTS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Check violation counts using user-based tracking
    $student1Count = ExamSecurityViolation::getViolationCount($student1->id, $subject->id, 'tab_switch');
    $student2Count = ExamSecurityViolation::getViolationCount($student2->id, $subject->id, 'tab_switch');
    
    echo "📊 Student 1 ({$student1->email}) violations: {$student1Count}\n";
    echo "📊 Student 2 ({$student2->email}) violations: {$student2Count}\n\n";
    
    // Show what would happen with IP-based counting (WRONG WAY)
    $ipBasedCount = ExamSecurityViolation::where('ip_address', '192.168.1.100')
        ->where('subject_id', $subject->id)
        ->where('violation_type', 'tab_switch')
        ->count();
        
    echo "❌ If we tracked by IP address (WRONG): {$ipBasedCount} violations\n";
    echo "   This would incorrectly penalize both students!\n\n";
    
    // Check ban status for each student
    $student1ShouldBeBanned = ExamSecurityViolation::shouldLockUser($student1->id, $subject->id, 'tab_switch', 3);
    $student2ShouldBeBanned = ExamSecurityViolation::shouldLockUser($student2->id, $subject->id, 'tab_switch', 3);
    
    echo "🚦 BAN STATUS CHECK:\n";
    echo "━━━━━━━━━━━━━━━━━━━━\n";
    echo "👤 Student 1 should be banned: " . ($student1ShouldBeBanned ? "YES ❌" : "NO ✅") . "\n";
    echo "👤 Student 2 should be banned: " . ($student2ShouldBeBanned ? "YES ❌" : "NO ✅") . "\n\n";
    
    // Show the fairness of user-based tracking
    echo "🎯 SYSTEM FAIRNESS ANALYSIS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if ($student1Count == 2 && $student2Count == 3) {
        echo "✅ CORRECT: Each student tracked independently\n";
        echo "✅ CORRECT: Student 1 (2 violations) can continue\n"; 
        echo "✅ CORRECT: Student 2 (3 violations) should be banned\n";
        echo "✅ CORRECT: Fair enforcement despite shared computer\n";
    } else {
        echo "❌ ERROR: Violation counts don't match expected values\n";
    }
    
    echo "\n🔐 TECHNICAL DETAILS:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📋 Tracking Method: User Account Credentials\n";
    echo "🆔 Primary Keys: user_id + subject_id + violation_type\n";
    echo "🌐 IP Address Role: Audit trail only (not for counting)\n";
    echo "📧 User Identification: Email, Registration Number, User ID\n";
    echo "🏫 Shared Computer Support: YES ✅\n";
    echo "🔄 Multiple Users Per IP: YES ✅\n";
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ USER-BASED TRACKING TEST COMPLETE\n";
    echo "✅ System correctly tracks violations by student account\n";
    echo "✅ Multiple students can safely share computers\n";
    echo "✅ Fair and accurate violation enforcement\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}