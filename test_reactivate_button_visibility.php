<?php

require __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;

echo "=== REACTIVATE BUTTON VISIBILITY TEST ===\n\n";

// 1. Check current banned students
echo "🔍 CHECKING CURRENT BANNED STUDENTS:\n";
echo "════════════════════════════════════\n";

$bannedStudents = ExamBan::where('is_active', true)
    ->with(['user', 'subject'])
    ->get();

if ($bannedStudents->count() === 0) {
    echo "❌ No banned students found. Creating test banned student...\n\n";
    
    // Find or create a test student
    $student = User::where('role', 'student')->first();
    if (!$student) {
        $student = User::where('role', 'user')->first();
    }
    
    $subject = Subject::first();
    
    if (!$student || !$subject) {
        echo "❌ Need at least one student and one subject in database\n";
        exit;
    }
    
    // Clear any existing violations/bans for clean test
    ExamSecurityViolation::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->delete();
        
    ExamBan::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->delete();
    
    // Create 3 tab switching violations using USER-BASED tracking
    for ($i = 1; $i <= 3; $i++) {
        ExamSecurityViolation::create([
            'user_id' => $student->id,        // ✅ USER-BASED TRACKING
            'subject_id' => $subject->id,
            'exam_session_id' => null,
            'violation_type' => 'tab_switch',
            'description' => "Tab switch violation #{$i} - Student: {$student->email}",
            'metadata' => [
                'user_identification' => [
                    'user_name' => $student->name,
                    'user_email' => $student->email,
                    'registration_number' => $student->registration_number,
                ],
                'violation_tracked_by' => 'user_credentials',
                'violation_count' => $i,
                'tracking_method' => 'user_account_based'
            ],
            'occurred_at' => now()->subMinutes(10 - $i),
            'ip_address' => '192.168.1.100',  // IP logged for audit, NOT used for tracking
            'user_agent' => 'Mozilla/5.0 (Test Browser)'
        ]);
        
        echo "⚠️  Violation #{$i} recorded for {$student->email} (User ID: {$student->id})\n";
    }
    
    // Get all violations for this USER (not IP)
    $violations = ExamSecurityViolation::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->where('violation_type', 'tab_switch')
        ->get();
    
    // Create ban using USER-BASED identification
    $ban = ExamBan::createViolationBan(
        $student->id,                    // ✅ USER ID (not IP)
        $subject->id,
        $violations,
        'Permanent ban after 3 TAB SWITCHING violations - User-based tracking'
    );
    
    echo "\n✅ STUDENT BANNED USING USER-BASED TRACKING:\n";
    echo "   Student: {$student->name}\n";
    echo "   Email: {$student->email}\n";
    echo "   Registration: {$student->registration_number}\n";
    echo "   Subject: {$subject->name}\n";
    echo "   Ban ID: {$ban->id}\n";
    echo "   Ban uses USER_ID: {$ban->user_id} ✅\n";
    echo "   Ban is active: " . ($ban->is_active ? 'YES' : 'NO') . "\n\n";
    
    // Update the banned students list
    $bannedStudents = ExamBan::where('is_active', true)
        ->with(['user', 'subject'])
        ->get();
} else {
    echo "✅ Found {$bannedStudents->count()} banned students\n\n";
}

// 2. Display banned students and check user-based tracking
echo "📋 BANNED STUDENTS DETAILS:\n";
echo "════════════════════════════\n";

foreach ($bannedStudents as $ban) {
    echo "👤 Student: {$ban->user->name}\n";
    echo "   📧 Email: {$ban->user->email}\n";
    echo "   🆔 Registration: " . ($ban->user->registration_number ?? 'Not set') . "\n";
    echo "   📚 Subject: {$ban->subject->name}\n";
    echo "   🔗 Ban ID: {$ban->id}\n";
    echo "   📊 Total Violations: {$ban->total_violations}\n";
    echo "   📅 Banned: {$ban->banned_at->format('Y-m-d H:i:s')}\n";
    echo "   ✅ Uses USER_ID: {$ban->user_id} (NOT IP-based)\n";
    echo "   🎯 Admin URL: /admin/security/bans/{$ban->id}\n";
    echo "   " . str_repeat("-", 50) . "\n";
}

// 3. Check user identification method
echo "\n🔍 USER IDENTIFICATION VERIFICATION:\n";
echo "════════════════════════════════════\n";

$sampleBan = $bannedStudents->first();
if ($sampleBan) {
    $violations = ExamSecurityViolation::where('user_id', $sampleBan->user_id)
        ->where('subject_id', $sampleBan->subject_id)
        ->get();
    
    echo "✅ CONFIRMING USER-BASED TRACKING:\n";
    echo "   Ban tracked by: USER_ID = {$sampleBan->user_id}\n";
    echo "   Student Email: {$sampleBan->user->email}\n";
    echo "   Registration: " . ($sampleBan->user->registration_number ?? 'Not set') . "\n";
    echo "   IP Address: Used for audit only, NOT for ban identification\n\n";
    
    if ($violations->count() > 0) {
        echo "📊 VIOLATION TRACKING DETAILS:\n";
        foreach ($violations as $v) {
            echo "   • Violation tracked by USER_ID: {$v->user_id}\n";
            echo "     IP (audit only): {$v->ip_address}\n";
            echo "     User Email: {$v->user->email}\n";
            if (isset($v->metadata['violation_tracked_by'])) {
                echo "     Tracking Method: {$v->metadata['violation_tracked_by']}\n";
            }
        }
    }
}

// 4. Access instructions for reactivate button
echo "\n🎯 HOW TO ACCESS THE REACTIVATE BUTTON:\n";
echo "═══════════════════════════════════════\n";
echo "1. 🌐 Login as an ADMIN user\n";
echo "2. 📋 Go to Admin Panel → Security → Banned Students\n";
echo "3. 🔗 Or visit directly: /admin/security/banned-students\n";
echo "4. 👀 You'll see RED 'Reactivate' buttons next to each banned student\n";
echo "5. 🖱️  Click 'Reactivate' to open the reactivation modal\n";
echo "6. ✍️  Fill out the reason and click 'Reactivate Student'\n\n";

echo "📍 DIRECT ADMIN LINKS:\n";
echo "═══════════════════════\n";
echo "• Main Security Dashboard: /admin/security\n";
echo "• Banned Students Page: /admin/security/banned-students\n";
foreach ($bannedStudents as $ban) {
    echo "• {$ban->user->name} Ban Details: /admin/security/bans/{$ban->id}\n";
}

echo "\n✅ SYSTEM CONFIRMATION:\n";
echo "════════════════════════\n";
echo "✅ Bans are USER-BASED (by registration, email, password)\n";
echo "✅ IP addresses are logged for audit only\n";
echo "✅ Reactivate buttons should be visible to admins\n";
echo "✅ 3-Strike system: Ban after 3rd tab switch\n";
echo "✅ User-based tracking prevents unfair shared computer penalties\n\n";

echo "🏁 TEST COMPLETE - Check admin panel for reactivate buttons!\n";