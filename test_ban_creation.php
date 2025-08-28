<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use Illuminate\Support\Facades\DB;

echo "🧪 TESTING BAN CREATION PROCESS\n";
echo "================================\n\n";

try {
    // Get a test user and subject
    $user = User::where('role', 'user')->first();
    $subject = Subject::first();
    
    if (!$user) {
        echo "❌ No users found with role 'user'\n";
        exit;
    }
    
    if (!$subject) {
        echo "❌ No subjects found\n";
        exit;
    }
    
    echo "👤 Test User: {$user->name} (ID: {$user->id})\n";
    echo "📚 Test Subject: {$subject->name} (ID: {$subject->id})\n\n";
    
    // Check current state
    $existingViolations = ExamSecurityViolation::where('user_id', $user->id)
        ->where('subject_id', $subject->id)
        ->where('violation_type', 'tab_switch')
        ->count();
    
    $existingBans = ExamBan::where('user_id', $user->id)
        ->where('subject_id', $subject->id)
        ->where('is_active', true)
        ->count();
    
    echo "📊 Current State:\n";
    echo "   - Existing tab switch violations: {$existingViolations}\n";
    echo "   - Existing active bans: {$existingBans}\n\n";
    
    // Create a tab switch violation
    echo "🚨 Creating tab switch violation...\n";
    $violation = ExamSecurityViolation::create([
        'user_id' => $user->id,
        'subject_id' => $subject->id,
        'exam_session_id' => null,
        'violation_type' => 'tab_switch',
        'description' => 'Test tab switch violation for ban creation',
        'metadata' => [
            'test' => true,
            'created_by' => 'test_ban_creation.php',
            'timestamp' => now()->toISOString()
        ],
        'occurred_at' => now(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Test Script'
    ]);
    
    echo "✅ Violation created with ID: {$violation->id}\n\n";
    
    // Now manually create a ban using the same logic as ViolationController
    echo "🚫 Creating ban record...\n";
    
    $existingBan = ExamBan::where('user_id', $user->id)
        ->where('subject_id', $subject->id)
        ->where('is_active', true)
        ->first();
    
    if (!$existingBan) {
        $ban = ExamBan::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'ban_reason' => 'TEST BAN: Tab switching detected - immediate ban policy',
            'violation_details' => [
                [
                    'type' => 'tab_switch',
                    'description' => 'Test tab switch violation for ban creation',
                    'occurred_at' => now()->toISOString(),
                    'student_identification' => [
                        'registration_number' => $user->registration_number ?? 'TEST' . $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                        'user_id' => $user->id
                    ],
                    'tracking_method' => 'registration_and_email_based',
                    'test' => true
                ]
            ],
            'total_violations' => 1,
            'banned_at' => now(),
            'is_active' => true,
            'is_permanent' => true
        ]);
        
        echo "✅ Ban created with ID: {$ban->id}\n";
        echo "   - User ID: {$ban->user_id}\n";
        echo "   - Subject ID: {$ban->subject_id}\n";
        echo "   - Is Active: " . ($ban->is_active ? 'YES' : 'NO') . "\n";
        echo "   - Is Permanent: " . ($ban->is_permanent ? 'YES' : 'NO') . "\n";
        echo "   - Can Request Reactivation: " . ($ban->can_request_reactivation ? 'YES' : 'NO') . "\n";
        echo "   - Banned At: {$ban->banned_at}\n\n";
    } else {
        echo "⚠️ User already has an active ban for this subject (ID: {$existingBan->id})\n\n";
    }
    
    // Verify the ban is visible to admin dashboard
    echo "🔍 Verifying admin dashboard query...\n";
    
    $adminBannedStudents = ExamBan::with(['user:id,name,email,registration_number', 'subject:id,name'])
        ->where('is_active', true)
        ->orderBy('banned_at', 'desc')
        ->get();
    
    echo "📋 Active bans found by admin query: {$adminBannedStudents->count()}\n";
    
    foreach ($adminBannedStudents as $ban) {
        echo "   - {$ban->user->name} banned from {$ban->subject->name} (ID: {$ban->id})\n";
    }
    
    if ($adminBannedStudents->count() > 0) {
        echo "\n✅ SUCCESS: Admin dashboard should now show banned students!\n";
        echo "🌐 Visit: /admin/security to see the results\n";
    } else {
        echo "\n❌ ISSUE: Admin dashboard query returned no results\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 TEST COMPLETE\n";