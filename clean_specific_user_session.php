<?php
/**
 * Clean Active Session for Specific User
 * Clean up the active session that might be causing ban message confusion
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ExamSession;

echo "🧹 CLEANING ACTIVE SESSION FOR SPECIFIC USER\n";
echo "=============================================\n\n";

$email = 'emeka.nwosu@example.com';
$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ User not found: {$email}\n";
    exit;
}

echo "✅ Found user: {$user->name} (ID: {$user->id})\n\n";

// Get active sessions
$activeSessions = ExamSession::where('user_id', $user->id)
    ->where('is_active', true)
    ->with('subject')
    ->get();

if ($activeSessions->count() > 0) {
    echo "🔧 Found {$activeSessions->count()} active session(s) to clean:\n\n";
    
    foreach ($activeSessions as $session) {
        $subjectName = $session->subject ? $session->subject->name : 'Unknown Subject';
        $isExpired = $session->isExpired();
        
        echo "Session ID {$session->id}:\n";
        echo "  📖 Subject: {$subjectName}\n";
        echo "  📅 Started: {$session->started_at}\n";
        echo "  ⏰ Expired: " . ($isExpired ? 'YES' : 'NO') . "\n";
        
        // Mark as completed
        $session->markAsCompleted(true);
        echo "  ✅ Session cleaned up\n\n";
    }
    
    echo "✅ All sessions cleaned up successfully!\n";
    echo "🔄 User should now see subjects as 'Pending' instead of 'In Progress'\n\n";
    
} else {
    echo "ℹ️ No active sessions found to clean\n\n";
}

echo "💡 NEXT STEPS:\n";
echo "==============\n";
echo "1. Have the user refresh their browser\n";
echo "2. Clear browser cache and cookies\n";
echo "3. Try starting an exam again\n";
echo "4. The false ban message should now be gone\n\n";

echo "✅ Session cleanup complete!\n";
?>