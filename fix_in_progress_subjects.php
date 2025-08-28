<?php
/**
 * Fix "In Progress" Subjects Issue
 * This script diagnoses and fixes the issue where all subjects show as "In Progress" instead of "Pending"
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSession;
use App\Models\UserScore;
use App\Models\ExamBan;
use Carbon\Carbon;

echo "🔍 DIAGNOSING 'IN PROGRESS' SUBJECTS ISSUE\n";
echo "==========================================\n\n";

// Step 1: Get current user (you can replace this with a specific user ID)
echo "📋 Enter your student registration number or email: ";
$handle = fopen("php://stdin", "r");
$identifier = trim(fgets($handle));
fclose($handle);

$user = User::where('email', $identifier)
    ->orWhere('registration_number', $identifier)
    ->first();

if (!$user) {
    echo "❌ User not found with identifier: {$identifier}\n";
    exit;
}

echo "✅ Found user: {$user->name} (ID: {$user->id})\n";
echo "📧 Email: {$user->email}\n";
echo "🎓 Class: " . ($user->classModel->name ?? 'Not assigned') . "\n\n";

// Step 2: Check available subjects for this user
$availableSubjects = Subject::where('class_id', $user->class_id)->get();
echo "📚 Available subjects for your class: {$availableSubjects->count()}\n\n";

// Step 3: Check active exam sessions (THIS IS LIKELY THE PROBLEM)
echo "🔍 CHECKING ACTIVE EXAM SESSIONS (ROOT CAUSE):\n";
echo "==============================================\n";

$activeSessions = ExamSession::where('user_id', $user->id)
    ->where('is_active', true)
    ->with('subject')
    ->get();

if ($activeSessions->count() > 0) {
    echo "❌ PROBLEM FOUND: {$activeSessions->count()} active exam sessions making subjects show as 'In Progress'\n\n";
    
    foreach ($activeSessions as $session) {
        $subjectName = $session->subject->name ?? 'Unknown Subject';
        $isExpired = $session->isExpired();
        $remaining = $session->remaining_time;
        
        echo "Session ID {$session->id}:\n";
        echo "  📖 Subject: {$subjectName}\n";
        echo "  📅 Started: {$session->started_at}\n";
        echo "  ⏰ Expires: {$session->expires_at}\n";
        echo "  ⏱️  Remaining: {$remaining} seconds\n";
        echo "  💀 Expired: " . ($isExpired ? 'YES' : 'NO') . "\n";
        echo "  📍 Question: {$session->current_question_index}\n\n";
    }
    
    echo "🛠️ FIXING THE ISSUE:\n";
    echo "====================\n";
    
    foreach ($activeSessions as $session) {
        $subjectName = $session->subject->name ?? 'Unknown Subject';
        
        if ($session->isExpired()) {
            // Mark expired sessions as completed
            $session->markAsCompleted(true);
            echo "✅ Marked expired session as auto-submitted: {$subjectName}\n";
        } else {
            // Ask what to do with non-expired sessions
            echo "\n🤔 Session for '{$subjectName}' is still active (not expired).\n";
            echo "   Started: {$session->started_at}\n";
            echo "   Remaining: " . gmdate("H:i:s", $session->remaining_time) . "\n";
            echo "\nWhat would you like to do?\n";
            echo "1. Complete and submit this session (lose progress)\n";
            echo "2. Keep it active (you can resume this exam)\n";
            echo "3. Auto-submit with current answers\n";
            echo "Enter choice (1-3): ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $session->markAsCompleted(false);
                    echo "✅ Session completed: {$subjectName}\n";
                    break;
                case '2':
                    echo "✅ Keeping session active: {$subjectName}\n";
                    break;
                case '3':
                    $session->markAsCompleted(true);
                    echo "✅ Session auto-submitted: {$subjectName}\n";
                    break;
                default:
                    echo "⏩ Skipping: {$subjectName}\n";
                    break;
            }
        }
    }
    
} else {
    echo "✅ No active exam sessions found - this is not the issue\n\n";
}

// Step 4: Check for completed subjects
echo "📊 CHECKING COMPLETED SUBJECTS:\n";
echo "==============================\n";

$completedSubjects = UserScore::where('user_id', $user->id)
    ->with('subject')
    ->get();

if ($completedSubjects->count() > 0) {
    echo "✅ Found {$completedSubjects->count()} completed subject(s):\n\n";
    
    foreach ($completedSubjects as $score) {
        $subjectName = $score->subject->name ?? 'Unknown Subject';
        $percentage = $score->total_questions > 0 ? round(($score->score / $score->total_questions) * 100, 1) : 0;
        echo "  📖 {$subjectName}: {$score->score}/{$score->total_questions} ({$percentage}%)\n";
    }
} else {
    echo "ℹ️ No completed subjects found\n";
}

// Step 5: Check for bans
echo "\n🚫 CHECKING FOR BANS:\n";
echo "====================\n";

$activeBans = ExamBan::where('user_id', $user->id)
    ->where('is_active', true)
    ->with('subject')
    ->get();

if ($activeBans->count() > 0) {
    echo "❌ Found {$activeBans->count()} active ban(s):\n\n";
    
    foreach ($activeBans as $ban) {
        $subjectName = $ban->subject->name ?? 'Unknown Subject';
        echo "  🚫 {$subjectName}: {$ban->ban_reason}\n";
        echo "     Banned: {$ban->banned_at}\n";
        echo "     Violations: {$ban->total_violations}\n\n";
    }
} else {
    echo "✅ No active bans found\n";
}

// Step 6: Final summary and recommendations
echo "\n💡 SUMMARY & RECOMMENDATIONS:\n";
echo "=============================\n";

$currentActiveSessions = ExamSession::where('user_id', $user->id)
    ->where('is_active', true)
    ->count();

if ($currentActiveSessions > 0) {
    echo "⚠️ You still have {$currentActiveSessions} active session(s)\n";
    echo "   These will show subjects as 'In Progress' on your dashboard\n";
    echo "   You can either:\n";
    echo "   - Resume and complete the exam(s)\n";
    echo "   - Run this script again to clean them up\n\n";
} else {
    echo "✅ All exam sessions are now clean!\n";
    echo "   Your dashboard should now show subjects as 'Pending' instead of 'In Progress'\n\n";
}

echo "🔄 NEXT STEPS:\n";
echo "1. Refresh your dashboard page\n";
echo "2. Subjects should now show correct status (Pending/Completed)\n";
echo "3. If issues persist, check for browser cache or logout/login\n\n";

echo "✅ Diagnostic and fix complete!\n";
?>