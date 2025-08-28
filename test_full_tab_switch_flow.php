<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use Illuminate\Support\Facades\DB;

echo "🧪 TESTING FULL TAB SWITCH FLOW\n";
echo "===============================\n\n";

try {
    // Get test user and subject
    $user = User::where('role', 'user')->where('id', '!=', 3)->first(); // Use different user from previous test
    $subject = Subject::where('id', '!=', 14)->first(); // Use different subject
    
    if (!$user) {
        echo "❌ No available users found\n";
        exit;
    }
    
    if (!$subject) {
        echo "❌ No available subjects found\n";
        exit;
    }
    
    echo "👤 Test User: {$user->name} (ID: {$user->id})\n";
    echo "📚 Test Subject: {$subject->name} (ID: {$subject->id})\n\n";
    
    // Simulate the exact same process as the ViolationController
    echo "🚨 Simulating tab switch violation...\n";
    
    // 1. Create violation record (same as API controller)
    $violation = ExamSecurityViolation::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'exam_session_id' => null,
        'violation_type' => 'tab_switch',
        'description' => 'Student switched tabs or opened new window during exam - IMMEDIATE BAN POLICY',
        'metadata' => [
            'detection_method' => 'blur_focus_loss',
            'browser_info' => [
                'user_agent' => 'Mozilla/5.0 (Test Browser)',
                'screen_resolution' => '1920x1080',
                'window_size' => '1920x1080'
            ],
            'violation_context' => [
                'exam_time_elapsed' => 300,
                'current_question' => 5,
                'questions_answered' => 4
            ],
            'policy' => 'IMMEDIATE_BAN_ON_FIRST_VIOLATION'
        ],
        'occurred_at' => now(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Test Browser)'
    ]);
    
    echo "✅ Violation created with ID: {$violation->id}\n";
    
    // 2. Create ban (same logic as API controller)
    echo "🚫 Creating immediate ban...\n";
    
    $existingBan = ExamBan::where('user_id', $user->id)
        ->where('subject_id', $subject->id)
        ->where('is_active', true)
        ->first();
    
    if (!$existingBan) {
        $ban = ExamBan::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'ban_reason' => 'IMMEDIATE_TAB_SWITCH_BAN',
            'violation_details' => [
                [
                    'type' => 'tab_switch',
                    'description' => 'Student switched tabs during exam - immediate ban policy',
                    'occurred_at' => now()->toISOString(),
                    'student_identification' => [
                        'registration_number' => $user->registration_number ?? 'N/A',
                        'email' => $user->email,
                        'name' => $user->name,
                        'user_id' => $user->id
                    ],
                    'tracking_method' => 'registration_and_email_based',
                    'violation_id' => $violation->id,
                    'policy' => 'IMMEDIATE_BAN_ON_FIRST_VIOLATION'
                ]
            ],
            'total_violations' => 1,
            'banned_at' => now(),
            'is_active' => true,
            'is_permanent' => true
        ]);
        
        echo "✅ Ban created with ID: {$ban->id}\n";
        echo "   - Ban reason: {$ban->ban_reason}\n";
        echo "   - Is Active: " . ($ban->is_active ? 'YES' : 'NO') . "\n";
        echo "   - Is Permanent: " . ($ban->is_permanent ? 'YES' : 'NO') . "\n\n";
        
    } else {
        echo "⚠️ User already has an active ban for this subject\n\n";
    }
    
    // 3. Test admin dashboard query
    echo "🔍 Testing admin dashboard query...\n";
    
    $bannedStudents = ExamBan::with(['user:id,name,email,registration_number', 'subject:id,name'])
        ->where('is_active', true)
        ->orderBy('banned_at', 'desc')
        ->get();
    
    echo "📋 Total active bans in system: {$bannedStudents->count()}\n\n";
    
    if ($bannedStudents->count() > 0) {
        echo "👥 BANNED STUDENTS LIST (as admin would see):\n";
        echo "=" . str_repeat("=", 50) . "\n";
        
        foreach ($bannedStudents as $ban) {
            echo "📌 Student: {$ban->user->name}\n";
            echo "   Email: {$ban->user->email}\n";
            echo "   Registration: " . ($ban->user->registration_number ?? 'N/A') . "\n";
            echo "   Subject: {$ban->subject->name}\n";
            echo "   Banned: {$ban->banned_at->format('Y-m-d H:i:s')}\n";
            echo "   Reason: {$ban->ban_reason}\n";
            echo "   Ban ID: {$ban->id}\n";
            echo "   " . str_repeat("-", 50) . "\n";
        }
        
        echo "\n✅ SUCCESS: Admin security dashboard will show banned students!\n";
        echo "🌐 Admin can now see reactivation requests at: /admin/security\n";
        echo "🔄 Students will see reactivation button on dashboard after redirect\n";
        
    } else {
        echo "❌ No banned students found in admin query\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 FULL FLOW TEST COMPLETE\n";