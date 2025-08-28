<?php
/**
 * Debug Specific User Ban Issues
 * Check why emeka.nwosu@example.com is seeing "tabswutching detected u are banned"
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;
use App\Models\ExamSession;

echo "🔍 DEBUGGING USER-SPECIFIC BAN ISSUES\n";
echo "=====================================\n\n";

// Check the specific user mentioned
$email = 'emeka.nwosu@example.com';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ User not found: {$email}\n";
    echo "Let me check all users with similar names...\n\n";
    
    $similarUsers = User::where('email', 'like', '%emeka%')
        ->orWhere('email', 'like', '%nwosu%')
        ->get();
        
    if ($similarUsers->count() > 0) {
        echo "📋 Found similar users:\n";
        foreach ($similarUsers as $u) {
            echo "   {$u->email} - {$u->name} (ID: {$u->id})\n";
        }
        $user = $similarUsers->first();
        echo "\n✅ Using: {$user->email}\n\n";
    } else {
        echo "No similar users found. Exiting.\n";
        exit;
    }
}

echo "👤 USER DETAILS:\n";
echo "================\n";
echo "Name: {$user->name}\n";
echo "Email: {$user->email}\n";
echo "ID: {$user->id}\n";
echo "Class ID: {$user->class_id}\n\n";

// Check available subjects
$subjects = Subject::where('class_id', $user->class_id)->get();
echo "📚 AVAILABLE SUBJECTS ({$subjects->count()}):\n";
echo "========================================\n";
foreach ($subjects as $subject) {
    echo "   ID: {$subject->id} - {$subject->name}\n";
}
echo "\n";

// Check active bans
echo "🚫 ACTIVE BANS:\n";
echo "===============\n";
$activeBans = ExamBan::where('user_id', $user->id)
    ->where('is_active', true)
    ->with('subject')
    ->get();

if ($activeBans->count() > 0) {
    echo "❌ Found {$activeBans->count()} active ban(s):\n\n";
    foreach ($activeBans as $ban) {
        $subjectName = $ban->subject ? $ban->subject->name : 'Unknown Subject';
        echo "   📖 Subject: {$subjectName} (ID: {$ban->subject_id})\n";
        echo "      Ban ID: {$ban->id}\n";
        echo "      Reason: {$ban->ban_reason}\n";
        echo "      Created: {$ban->banned_at}\n";
        echo "      Violations: {$ban->total_violations}\n";
        echo "      Permanent: " . ($ban->is_permanent ? 'YES' : 'NO') . "\n\n";
    }
} else {
    echo "✅ No active bans found - user should NOT see ban messages\n\n";
}

// Check all bans (including inactive)
echo "📋 ALL BANS (ACTIVE + INACTIVE):\n";
echo "================================\n";
$allBans = ExamBan::where('user_id', $user->id)
    ->with('subject')
    ->orderBy('banned_at', 'desc')
    ->get();

if ($allBans->count() > 0) {
    foreach ($allBans as $ban) {
        $subjectName = $ban->subject ? $ban->subject->name : 'Unknown Subject';
        $status = $ban->is_active ? '🔴 ACTIVE' : '✅ INACTIVE';
        echo "   {$status} - {$subjectName}: {$ban->ban_reason}\n";
    }
} else {
    echo "✅ No bans found at all\n";
}

// Check recent violations
echo "\n🔍 RECENT VIOLATIONS:\n";
echo "====================\n";
$violations = ExamSecurityViolation::where('user_id', $user->id)
    ->with('subject')
    ->orderBy('occurred_at', 'desc')
    ->take(10)
    ->get();

if ($violations->count() > 0) {
    foreach ($violations as $violation) {
        $subjectName = $violation->subject ? $violation->subject->name : 'Unknown Subject';
        echo "   {$violation->occurred_at} - {$violation->violation_type} - {$subjectName}\n";
    }
} else {
    echo "✅ No violations found\n";
}

// Check active exam sessions
echo "\n📝 ACTIVE EXAM SESSIONS:\n";
echo "=======================\n";
$activeSessions = ExamSession::where('user_id', $user->id)
    ->where('is_active', true)
    ->with('subject')
    ->get();

if ($activeSessions->count() > 0) {
    echo "⚠️ Found {$activeSessions->count()} active session(s):\n";
    foreach ($activeSessions as $session) {
        $subjectName = $session->subject ? $session->subject->name : 'Unknown Subject';
        echo "   📖 {$subjectName}: Started {$session->started_at}\n";
    }
} else {
    echo "✅ No active sessions\n";
}

// Test ban checking logic for each subject
echo "\n🧪 TESTING BAN CHECK FOR EACH SUBJECT:\n";
echo "======================================\n";

foreach ($subjects as $subject) {
    // Use the same logic as ViolationDetectionService
    $ban = ExamBan::where('user_id', $user->id)
        ->where('subject_id', $subject->id)
        ->where('is_active', true)
        ->first();
        
    $isBanned = $ban ? 'YES' : 'NO';
    echo "   {$subject->name}: Banned = {$isBanned}";
    if ($ban) {
        echo " (Reason: {$ban->ban_reason})";
    }
    echo "\n";
}

// Check if there's a hardcoded ban message somewhere
echo "\n🔍 POSSIBLE CAUSES OF FAKE BAN MESSAGE:\n";
echo "=======================================\n";

if ($activeBans->count() === 0 && $violations->count() === 0) {
    echo "❓ USER HAS NO BANS OR VIOLATIONS BUT SEES BAN MESSAGE\n";
    echo "This suggests one of these issues:\n\n";
    
    echo "1. 🕐 AGGRESSIVE SECURITY TIMING ISSUE:\n";
    echo "   - lockExamInterface() starts too early (before 30 seconds)\n";
    echo "   - Tab switching detection triggers during page load\n";
    echo "   - Normal browser behavior interpreted as violation\n\n";
    
    echo "2. 🔄 CACHED/STALE DATA:\n";
    echo "   - Browser cache showing old ban status\n";
    echo "   - Laravel view cache not cleared\n";
    echo "   - Session data persisting incorrect ban status\n\n";
    
    echo "3. 📝 HARDCODED TEST MESSAGE:\n";
    echo "   - Test code left in production\n";
    echo "   - Debug message not removed\n";
    echo "   - Fake ban check returning true\n\n";
    
    echo "4. 🌐 WRONG USER CONTEXT:\n";
    echo "   - System checking bans for different user\n";
    echo "   - Session confusion between users\n";
    echo "   - Authentication issue\n\n";
}

echo "💡 RECOMMENDATIONS:\n";
echo "===================\n";

if ($activeBans->count() === 0) {
    echo "✅ User should NOT see any ban messages\n";
    echo "1. Clear browser cache and cookies\n";
    echo "2. Clear Laravel view cache: php artisan view:clear\n";
    echo "3. Check exam_simple.blade.php for hardcoded messages\n";
    echo "4. Verify lockExamInterface() timing (should be 30 seconds)\n";
    echo "5. Check if ban checking API is working correctly\n\n";
    
    echo "🧪 IMMEDIATE TEST:\n";
    echo "Try accessing an exam with these URLs:\n";
    foreach ($subjects as $subject) {
        echo "   /exam/{$subject->id}/start\n";
    }
    echo "\nIf ban message still appears, it's likely a code/timing issue.\n";
} else {
    echo "⚠️ User has active bans - ban message is correct\n";
    echo "Check admin dashboard to manage these bans if needed\n";
}

echo "\n✅ Debug complete!\n";
?>