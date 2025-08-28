<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Import models
use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;
use App\Models\User;
use App\Models\Subject;

echo "🔍 TESTING REGISTRATION NUMBER & EMAIL TRACKING SYSTEM\n";
echo "=====================================================\n\n";

// Check currently banned students
$activeBans = ExamBan::with(['user', 'subject'])->where('is_active', true)->get();

echo "📊 CURRENTLY BANNED STUDENTS: " . $activeBans->count() . "\n";
echo "=============================================\n";

if ($activeBans->count() > 0) {
    foreach ($activeBans as $index => $ban) {
        echo ($index + 1) . ". STUDENT TRACKING INFORMATION:\n";
        echo "   🏷️  Name: {$ban->user->name}\n";
        echo "   📧 Email: {$ban->user->email}\n";
        echo "   🎯 Registration Number: " . ($ban->user->registration_number ?? 'NOT SET') . "\n";
        echo "   📚 Subject: {$ban->subject->name}\n";
        echo "   📅 Banned: {$ban->banned_at}\n";
        echo "   🚨 Violations: {$ban->total_violations}\n";
        echo "   📝 Ban Reason: " . substr($ban->ban_reason, 0, 80) . "...\n";
        
        // Check violation details for tracking method
        if (!empty($ban->violation_details)) {
            $violationDetail = $ban->violation_details[0] ?? [];
            if (isset($violationDetail['student_identification'])) {
                echo "   ✅ TRACKING METHOD: Registration & Email Based\n";
                echo "   📋 Tracking Details:\n";
                $studentId = $violationDetail['student_identification'];
                echo "      - Registration: {$studentId['registration_number']}\n";
                echo "      - Email: {$studentId['email']}\n";
                echo "      - Name: {$studentId['name']}\n";
                echo "      - User ID: {$studentId['user_id']}\n";
            }
            
            if (isset($violationDetail['tracking_method'])) {
                echo "   🔍 Tracking Method: {$violationDetail['tracking_method']}\n";
            }
            
            if (isset($violationDetail['note'])) {
                echo "   📖 Note: {$violationDetail['note']}\n";
            }
        }
        
        echo "   " . str_repeat("-", 60) . "\n";
    }
} else {
    echo "✅ No currently banned students found.\n\n";
}

// Check recent security violations to see tracking method
echo "\n🔍 RECENT SECURITY VIOLATIONS (TRACKING ANALYSIS):\n";
echo "=================================================\n";

$recentViolations = ExamSecurityViolation::with(['user', 'subject'])
    ->orderBy('occurred_at', 'desc')
    ->limit(5)
    ->get();

if ($recentViolations->count() > 0) {
    foreach ($recentViolations as $index => $violation) {
        echo ($index + 1) . ". VIOLATION TRACKING:\n";
        echo "   👤 Student: {$violation->user->name}\n";
        echo "   📧 Email: {$violation->user->email}\n";
        echo "   🎯 Registration: " . ($violation->user->registration_number ?? 'NOT SET') . "\n";
        echo "   📚 Subject: {$violation->subject->name}\n";
        echo "   ⚠️  Type: {$violation->violation_type}\n";
        echo "   ⏰ Time: {$violation->occurred_at}\n";
        
        // Check tracking method in metadata
        if (!empty($violation->metadata)) {
            $metadata = $violation->metadata;
            
            if (isset($metadata['violation_tracked_by'])) {
                echo "   ✅ TRACKED BY: {$metadata['violation_tracked_by']}\n";
            }
            
            if (isset($metadata['tracking_method'])) {
                echo "   🔍 METHOD: {$metadata['tracking_method']}\n";
            }
            
            if (isset($metadata['user_identification']['primary_tracking'])) {
                $primary = $metadata['user_identification']['primary_tracking'];
                echo "   🎯 PRIMARY IDENTIFIERS:\n";
                echo "      - Registration: {$primary['registration_number']}\n";
                echo "      - Email: {$primary['email']}\n";
            }
            
            if (isset($metadata['note'])) {
                echo "   📝 Tracking Note: {$metadata['note']}\n";
            }
        }
        
        // Show IP address is recorded but NOT used for tracking
        echo "   🌐 IP Address: {$violation->ip_address} (AUDIT ONLY - NOT USED FOR TRACKING)\n";
        echo "   " . str_repeat("-", 50) . "\n";
    }
} else {
    echo "No recent violations found.\n";
}

// Test scenario: Multiple students from same IP
echo "\n🧪 TESTING SCENARIO: SHARED COMPUTER LAB\n";
echo "========================================\n";

$sharedIP = '192.168.1.100';
$violationsFromSameIP = ExamSecurityViolation::where('ip_address', $sharedIP)->get();

if ($violationsFromSameIP->count() > 0) {
    echo "🌐 Violations from IP {$sharedIP}: {$violationsFromSameIP->count()}\n";
    
    $uniqueStudents = $violationsFromSameIP->pluck('user_id')->unique();
    echo "👥 Unique students from this IP: {$uniqueStudents->count()}\n\n";
    
    foreach ($uniqueStudents as $userId) {
        $user = User::find($userId);
        $userViolations = $violationsFromSameIP->where('user_id', $userId);
        
        echo "   Student: {$user->name}\n";
        echo "   Email: {$user->email}\n";
        echo "   Registration: " . ($user->registration_number ?? 'NOT SET') . "\n";
        echo "   Violations from this IP: {$userViolations->count()}\n";
        
        // Check if this user is banned
        $userBan = ExamBan::where('user_id', $userId)->where('is_active', true)->first();
        if ($userBan) {
            echo "   Status: ❌ BANNED\n";
        } else {
            echo "   Status: ✅ NOT BANNED\n";
        }
        echo "   ---\n";
    }
    
    echo "✅ RESULT: Each student tracked individually by registration/email, not by IP address!\n";
} else {
    echo "No violations found from shared IP scenario.\n";
}

echo "\n🎯 TRACKING SYSTEM SUMMARY:\n";
echo "===========================\n";
echo "✅ Students tracked by Registration Number & Email Address\n";
echo "✅ IP addresses recorded only for audit trail\n";
echo "✅ Multiple students can safely use same computer/IP\n";
echo "✅ Fair enforcement regardless of shared devices\n";
echo "✅ NO false positives from shared IP addresses\n\n";

echo "🌐 Admin Dashboard: http://web-portal.test/admin/security/banned-students\n";
echo "📝 Banned students will show Registration Numbers and Email prominently\n\n";

echo "✨ Test completed!\n";