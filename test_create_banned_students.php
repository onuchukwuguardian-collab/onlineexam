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

echo "🧪 CREATING TEST BANNED STUDENTS FOR ADMIN DASHBOARD\n";
echo "===================================================\n\n";

try {
    // Get a test student and subject
    $student = User::where('role', 'student')->first();
    $subject = Subject::first();
    
    if (!$student || !$subject) {
        echo "❌ ERROR: Need at least one student and one subject in database\n";
        exit(1);
    }
    
    echo "👤 Test Student: {$student->name} (ID: {$student->id}, Email: {$student->email})\n";
    echo "📚 Test Subject: {$subject->name} (ID: {$subject->id})\n\n";
    
    // Clear any existing violations/bans for clean test
    ExamSecurityViolation::where('user_id', $student->id)->where('subject_id', $subject->id)->delete();
    ExamBan::where('user_id', $student->id)->where('subject_id', $subject->id)->delete();
    
    echo "🧹 Cleared existing violations and bans for clean test\n\n";
    
    // Simulate 3 tab switching violations
    echo "📝 SIMULATING 3 TAB SWITCHING VIOLATIONS:\n";
    echo "=========================================\n";
    
    for ($i = 1; $i <= 3; $i++) {
        echo "\n⚠️  Creating violation #{$i}...\n";
        
        $violation = ExamSecurityViolation::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'exam_session_id' => null,
            'violation_type' => 'tab_switch',
            'description' => "Test tab switch violation #{$i} - Student switched away from exam tab",
            'metadata' => [
                'violation_count' => $i,
                'timestamp' => now()->toISOString(),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0 (TEST)',
                'screen_resolution' => '1920x1080',
                'window_size' => '1920x1080',
                'policy' => '3_STRIKE_POLICY',
                'test_data' => true
            ],
            'occurred_at' => now()->subMinutes(5 - $i),
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/91.0 (TEST)'
        ]);
        
        echo "   ✅ Violation #{$i} created (ID: {$violation->id})\n";
        echo "   📅 Occurred at: {$violation->occurred_at}\n";
        
        // Check violation count
        $totalCount = ExamSecurityViolation::getViolationCount($student->id, $subject->id, 'tab_switch');
        echo "   📊 Total violations: {$totalCount}\n";
    }
    
    // Create ban after 3rd violation
    echo "\n🚫 CREATING PERMANENT BAN AFTER 3RD VIOLATION:\n";
    echo "==============================================\n";
    
    // Get all violations for ban creation
    $allViolations = ExamSecurityViolation::where('user_id', $student->id)
        ->where('subject_id', $subject->id)
        ->where('violation_type', 'tab_switch')
        ->get();
    
    echo "📋 Found {$allViolations->count()} violations for ban creation\n";
    
    // Create the ban using the model method
    $ban = ExamBan::createViolationBan(
        $student->id,
        $subject->id,
        $allViolations,
        'Permanent ban after 3 TAB SWITCHING violations during online exam - 3-STRIKE POLICY'
    );
    
    echo "🔒 BAN CREATED SUCCESSFULLY!\n";
    echo "   📋 Ban ID: {$ban->id}\n";
    echo "   👤 Student: {$ban->user->name}\n";
    echo "   📚 Subject: {$ban->subject->name}\n";
    echo "   📅 Banned at: {$ban->banned_at}\n";
    echo "   🔢 Total violations: {$ban->total_violations}\n";
    echo "   ✅ Is active: " . ($ban->is_active ? 'YES' : 'NO') . "\n";
    echo "   ♾️  Is permanent: " . ($ban->is_permanent ? 'YES' : 'NO') . "\n";
    echo "   📝 Reason: {$ban->ban_reason}\n\n";
    
    // Verify ban status
    $isBanned = ExamBan::isBanned($student->id, $subject->id);
    echo "🔍 VERIFICATION:\n";
    echo "================\n";
    echo "✅ Student is banned: " . ($isBanned ? 'YES' : 'NO') . "\n";
    
    // Check admin page query
    $adminBannedStudents = ExamBan::with(['user', 'subject'])
        ->where('is_active', true)
        ->orderBy('banned_at', 'desc')
        ->get();
    
    echo "📊 Total active bans in system: {$adminBannedStudents->count()}\n";
    
    foreach ($adminBannedStudents as $adminBan) {
        echo "   - {$adminBan->user->name} banned from {$adminBan->subject->name} ({$adminBan->total_violations} violations)\n";
    }
    
    echo "\n🎯 SUCCESS!\n";
    echo "===========\n";
    echo "✅ Test banned student has been created\n";
    echo "✅ Admin should now see banned students at: http://web-portal.test/admin/security/banned-students\n";
    echo "✅ Admin can reactivate the student using the reactivate button\n\n";
    
    echo "🔗 ADMIN LINKS:\n";
    echo "===============\n";
    echo "📋 Banned Students: http://web-portal.test/admin/security/banned-students\n";
    echo "🔧 Security Dashboard: http://web-portal.test/admin/security/\n";
    echo "👤 Ban Details: http://web-portal.test/admin/security/ban-details/{$ban->id}\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "Test completed successfully! ✨\n";