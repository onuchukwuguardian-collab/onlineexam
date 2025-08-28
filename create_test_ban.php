<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ExamBan;
use App\Models\User;
use App\Models\Subject;

echo "🧪 CREATING TEST BAN FOR DEBUGGING...\n\n";

try {
    // Find a student user
    $user = User::where('role', 'user')->first();
    if (!$user) {
        echo "❌ No student users found. Creating test user...\n";
        $user = User::create([
            'name' => 'Test Student',
            'email' => 'test.student@example.com',
            'password' => bcrypt('password'),
            'role' => 'user',
            'registration_number' => 'TEST001'
        ]);
        echo "✅ Test user created: {$user->name} (ID: {$user->id})\n";
    } else {
        echo "✅ Found student user: {$user->name} (ID: {$user->id})\n";
    }

    // Find or create a subject
    $subject = Subject::first();
    if (!$subject) {
        echo "❌ No subjects found. Creating test subject...\n";
        $subject = Subject::create([
            'name' => 'Test Subject',
            'code' => 'TEST101',
            'class_id' => 1,
            'exam_duration_minutes' => 60
        ]);
        echo "✅ Test subject created: {$subject->name} (ID: {$subject->id})\n";
    } else {
        echo "✅ Found subject: {$subject->name} (ID: {$subject->id})\n";
    }

    // Check if user already has any ban for this subject (active or inactive)
    $existingBan = ExamBan::where('user_id', $user->id)
        ->where('subject_id', $subject->id)
        ->first();

    if ($existingBan) {
        echo "⚠️  User already has a ban (ID: {$existingBan->id})\n";
        echo "   Status: " . ($existingBan->is_active ? 'ACTIVE' : 'INACTIVE') . "\n";
        echo "   Reason: {$existingBan->ban_reason}\n";
        
        if (!$existingBan->is_active) {
            echo "🔄 Activating existing ban...\n";
            $existingBan->update([
                'is_active' => true,
                'ban_reason' => '🚫 REACTIVATED TEST BAN: For debugging reactivation system',
                'reactivated_at' => null,
                'reactivated_by' => null
            ]);
            echo "✅ Ban activated!\n";
        }
        $ban = $existingBan;
    } else {
        // Create test ban
        $ban = ExamBan::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'ban_reason' => '🚫 TEST BAN: Tab switching detected for debugging reactivation system',
            'violation_details' => [
                'test_violation' => true,
                'violation_type' => 'tab_switch',
                'created_for' => 'debugging_reactivation_system'
            ],
            'total_violations' => 1,
            'banned_at' => now(),
            'is_active' => true,
            'is_permanent' => true
        ]);

        echo "✅ TEST BAN CREATED!\n";
        echo "   Ban ID: {$ban->id}\n";
        echo "   User: {$user->name} ({$user->email})\n";
        echo "   Subject: {$subject->name}\n";
        echo "   Status: " . ($ban->is_active ? 'ACTIVE' : 'INACTIVE') . "\n";
        echo "   Reason: {$ban->ban_reason}\n";
    }

    // Verify the ban
    echo "\n🔍 VERIFICATION:\n";
    $activeBans = ExamBan::where('is_active', true)->count();
    $userBans = ExamBan::where('user_id', $user->id)->where('is_active', true)->count();
    
    echo "   Total active bans in system: {$activeBans}\n";
    echo "   Active bans for test user: {$userBans}\n";

    echo "\n📋 NEXT STEPS:\n";
    echo "   1. Login as: {$user->email} (password: password)\n";
    echo "   2. Visit: /student/reactivation\n";
    echo "   3. Submit a reactivation request\n";
    echo "   4. Login as admin and check: /admin/security/reactivation-requests\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n🎯 TEST BAN SETUP COMPLETE!\n";