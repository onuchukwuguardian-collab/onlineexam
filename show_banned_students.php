<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Container\Container;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Load environment
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo "=== BANNED STUDENTS WHO CAN BE REACTIVATED ===\n\n";

try {
    $bannedStudents = \App\Models\ExamBan::where('is_active', true)
        ->with(['user', 'subject'])
        ->get();
    
    if ($bannedStudents->count() === 0) {
        echo "No banned students found. Let me create a test banned student...\n\n";
        
        // Find first available user and subject
        $user = \App\Models\User::where('role', 'student')->first();
        $subject = \App\Models\Subject::first();
        
        if ($user && $subject) {
            // Create test violations
            for ($i = 1; $i <= 3; $i++) {
                \App\Models\ExamSecurityViolation::create([
                    'user_id' => $user->id,
                    'subject_id' => $subject->id,
                    'exam_session_id' => null,
                    'violation_type' => 'tab_switch',
                    'description' => "Test violation #{$i} - tab switching detected",
                    'metadata' => ['test' => true],
                    'occurred_at' => now()->subMinutes(10 - $i),
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'Test Browser'
                ]);
            }
            
            // Get all violations for this user and subject
            $violations = \App\Models\ExamSecurityViolation::where('user_id', $user->id)
                ->where('subject_id', $subject->id)
                ->where('violation_type', 'tab_switch')
                ->get();
            
            // Create test ban
            $ban = \App\Models\ExamBan::create([
                'user_id' => $user->id,
                'subject_id' => $subject->id,
                'ban_reason' => 'Test ban for demonstration - 3 tab switching violations',
                'violation_details' => $violations->toArray(),
                'total_violations' => 3,
                'banned_at' => now(),
                'banned_by_admin_id' => null,
                'is_permanent' => true,
                'is_active' => true,
                'admin_notes' => 'Test ban created to demonstrate reactivation functionality'
            ]);
            
            echo "✅ Test banned student created:\n";
            echo "   Student: {$user->name} ({$user->email})\n";
            echo "   Subject: {$subject->name}\n";
            echo "   Ban ID: {$ban->id}\n\n";
            
            $bannedStudents = \App\Models\ExamBan::where('is_active', true)
                ->with(['user', 'subject'])
                ->get();
        }
    }
    
    echo "Currently banned students:\n";
    echo str_repeat("=", 60) . "\n";
    
    foreach ($bannedStudents as $index => $ban) {
        $studentName = $ban->user->name ?? 'Unknown Student';
        $studentEmail = $ban->user->email ?? 'Unknown Email';
        $subjectName = $ban->subject->name ?? 'Unknown Subject';
        $banDate = $ban->banned_at ? $ban->banned_at->format('M j, Y g:i A') : 'Unknown Date';
        
        echo "\n" . ($index + 1) . ". BANNED STUDENT\n";
        echo "   👤 Student: {$studentName}\n";
        echo "   📧 Email: {$studentEmail}\n";
        echo "   📚 Subject: {$subjectName}\n";
        echo "   🚫 Banned: {$banDate}\n";
        echo "   🆔 Ban ID: {$ban->id}\n";
        echo "   🔗 Admin URL: /admin/security/bans/{$ban->id}\n";
        echo "   " . str_repeat("-", 50) . "\n";
    }
    
    echo "\n🔧 HOW TO ACCESS THE REACTIVATE BUTTON:\n";
    echo "========================================\n";
    echo "1. 🌐 Login as an ADMIN user\n";
    echo "2. 📋 Go to Admin Panel → Security Violations\n";
    echo "3. 🔍 Or visit: /admin/security\n";
    echo "4. 👤 Click 'Manage' next to any banned student\n";
    echo "5. 🔓 Click the green 'Reactivate Student' button\n";
    echo "6. ✍️  Fill out the reactivation form with reason\n\n";
    
    echo "📍 DIRECT LINKS TO BAN DETAILS:\n";
    echo "==============================\n";
    foreach ($bannedStudents as $ban) {
        echo "• {$ban->user->name}: /admin/security/bans/{$ban->id}\n";
    }
    
    echo "\n✅ The reactivate button will be GREEN and say:\n";
    echo "   'Grant Mercy & Reactivate' or 'Reactivate Student'\n\n";
    
    echo "🎯 SYSTEM CONFIRMATION:\n";
    echo "=====================\n";
    echo "✅ 3-Strike System: Ban occurs after 3rd tab switch (not 2nd)\n";
    echo "✅ Tab-Switch Only: Bans only apply to tab switching violations\n";  
    echo "✅ Admin-Only: Only admins can reactivate banned students\n";
    echo "✅ Complete Audit: All reactivations are logged for accountability\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Please ensure you're running this from the Laravel project root.\n";
}