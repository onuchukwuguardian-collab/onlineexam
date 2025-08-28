<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Subject;  
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;

echo "=== TESTING 3-STRIKE TAB SWITCHING SYSTEM ===\n\n";

// Find a test student and subject
$student = User::where('role', 'user')->first();
$subject = Subject::first();

if (!$student || !$subject) {
    echo "❌ Need at least one student and one subject in database\n";
    exit;
}

echo "Test Student: {$student->name} (ID: {$student->id})\n";
echo "Test Subject: {$subject->name} (ID: {$subject->id})\n\n";

// Clear any existing violations for clean test
ExamSecurityViolation::where('user_id', $student->id)
    ->where('subject_id', $subject->id)
    ->delete();
    
ExamBan::where('user_id', $student->id)
    ->where('subject_id', $subject->id)
    ->delete();

echo "Cleared existing violations for clean test\n\n";

// Simulate 3 tab switching violations
for ($i = 1; $i <= 3; $i++) {
    echo "=== VIOLATION $i ===\n";
    
    ExamSecurityViolation::recordViolation(
        $student->id,
        $subject->id,
        'tab_switch',
        "Student switched away from exam tab. Violation #$i",
        null,
        [
            'timestamp' => now()->toISOString(),
            'violation_count' => $i,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'screen_resolution' => '1920x1080'
        ]
    );
    
    $count = ExamSecurityViolation::getViolationCount($student->id, $subject->id, 'tab_switch');
    echo "Total violations: $count\n";
    
    if ($i >= 3) {
        // Create ban after 3rd violation
        $violations = ExamSecurityViolation::where('user_id', $student->id)
            ->where('subject_id', $subject->id)
            ->get();
            
        $ban = ExamBan::createViolationBan(
            $student->id,
            $subject->id,
            $violations,
            'Permanent ban after 3 security violations (tab switching)'
        );
        
        echo "✅ Student BANNED after 3rd violation (Ban ID: {$ban->id})\n";
        echo "Ban is active: " . ($ban->is_active ? 'YES' : 'NO') . "\n";
    } else {
        echo "⚠️ Warning issued - " . (3 - $i) . " more violations until ban\n";
    }
    echo "\n";
}

// Check if student is banned
$isBanned = ExamBan::isBanned($student->id, $subject->id);
echo "Student is currently banned: " . ($isBanned ? 'YES' : 'NO') . "\n";

// Show admin URLs for testing
echo "\n=== ADMIN TESTING URLS ===\n";
echo "Security Dashboard: /admin/security\n";

$ban = ExamBan::where('user_id', $student->id)
    ->where('subject_id', $subject->id)
    ->where('is_active', true)
    ->first();
    
if ($ban) {
    echo "Ban Details: /admin/security/bans/{$ban->id}\n";
    echo "Reactivation will be available in ban details page\n";
}

echo "\n=== TEST COMPLETE ===\n";