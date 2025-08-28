<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamBan;
use App\Models\User;
use App\Models\Subject;

echo "🔍 TESTING ADMIN BANNED STUDENTS CONTROLLER LOGIC\n";
echo "=================================================\n\n";

// Replicate the exact controller logic
echo "📋 Replicating SecurityViolationController->bannedStudents() logic...\n\n";

$search = null;
$perPage = 15;

$query = ExamBan::with(['user', 'subject', 'bannedByAdmin', 'reactivatedBy'])
    ->where('is_active', true)
    ->orderBy('banned_at', 'desc');

echo "🔍 Query: SELECT * FROM exam_bans WHERE is_active = 1 ORDER BY banned_at DESC\n\n";

$bannedStudents = $query->get(); // Using get() instead of paginate() for debugging
echo "📊 Query Result Count: " . $bannedStudents->count() . "\n\n";

// Get statistics
$stats = [
    'total_banned' => ExamBan::where('is_active', true)->count(),
    'banned_today' => ExamBan::where('is_active', true)
        ->whereDate('banned_at', today())->count(),
    'banned_this_week' => ExamBan::where('is_active', true)
        ->whereBetween('banned_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
    'total_reactivated' => ExamBan::where('is_active', false)
        ->whereNotNull('reactivated_at')->count()
];

echo "📊 STATISTICS:\n";
echo "==============\n";
echo "Total Banned (is_active=true): {$stats['total_banned']}\n";
echo "Banned Today: {$stats['banned_today']}\n";
echo "Banned This Week: {$stats['banned_this_week']}\n";
echo "Total Reactivated: {$stats['total_reactivated']}\n\n";

if ($bannedStudents->count() > 0) {
    echo "✅ FOUND BANNED STUDENTS - These should appear on admin page:\n";
    echo "============================================================\n";
    
    foreach ($bannedStudents as $index => $ban) {
        echo ($index + 1) . ". Ban ID: {$ban->id}\n";
        
        // Check for relationship issues
        if ($ban->user) {
            echo "   ✅ User: {$ban->user->name} ({$ban->user->email})\n";
            if ($ban->user->registration_number) {
                echo "   📝 Registration: {$ban->user->registration_number}\n";
            }
        } else {
            echo "   ❌ User: MISSING (user_id: {$ban->user_id})\n";
        }
        
        if ($ban->subject) {
            echo "   ✅ Subject: {$ban->subject->name}\n";
        } else {
            echo "   ❌ Subject: MISSING (subject_id: {$ban->subject_id})\n";
        }
        
        echo "   📅 Banned: {$ban->banned_at}\n";
        echo "   🚨 Active: " . ($ban->is_active ? 'TRUE' : 'FALSE') . "\n";
        echo "   📝 Reason: " . substr($ban->ban_reason, 0, 60) . "...\n";
        echo "   ---\n";
    }
} else {
    echo "❌ NO BANNED STUDENTS FOUND\n";
    echo "============================\n";
    echo "This is why the admin page shows 'no banned students'\n\n";
    
    // Check if there are any bans at all
    $anyBans = ExamBan::count();
    echo "🔍 Total ban records in database: {$anyBans}\n";
    
    if ($anyBans > 0) {
        echo "📋 Checking status of existing bans...\n";
        $allBans = ExamBan::with(['user', 'subject'])->get();
        
        foreach ($allBans as $ban) {
            echo "   Ban ID {$ban->id}: is_active = " . ($ban->is_active ? 'TRUE' : 'FALSE') . "\n";
            if (!$ban->is_active && $ban->reactivated_at) {
                echo "   └── Reactivated at: {$ban->reactivated_at}\n";
            }
        }
    }
}

// Test if we can create a test ban
echo "\n🧪 TESTING BAN CREATION:\n";
echo "========================\n";

$testUser = User::where('role', 'user')->first();
$testSubject = Subject::first();

if ($testUser && $testSubject) {
    echo "✅ Test user available: {$testUser->name}\n";
    echo "✅ Test subject available: {$testSubject->name}\n";
    
    // Check if this user already has a ban for this subject
    $existingBan = ExamBan::where('user_id', $testUser->id)
        ->where('subject_id', $testSubject->id)
        ->first();
    
    if ($existingBan) {
        echo "📋 Existing ban found for this user/subject:\n";
        echo "   Ban ID: {$existingBan->id}\n";
        echo "   Is Active: " . ($existingBan->is_active ? 'TRUE' : 'FALSE') . "\n";
        echo "   Banned At: {$existingBan->banned_at}\n";
        
        if (!$existingBan->is_active) {
            echo "💡 SUGGESTION: Reactivate this ban to test the admin page\n";
        }
    } else {
        echo "💡 SUGGESTION: Create a test ban to verify admin page functionality\n";
    }
} else {
    echo "❌ Missing test data (users or subjects)\n";
}

echo "\n✨ Test completed!\n";