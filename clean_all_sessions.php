<?php
/**
 * Quick Fix: Clean All Active Sessions
 * This will reset all subjects to "Pending" status
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ExamSession;

echo "🧹 QUICK FIX: CLEAN ALL ACTIVE SESSIONS\n";
echo "======================================\n\n";

// Get user by email (replace with your actual email)
$user = User::where('email', 'john.ade@example.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit;
}

echo "✅ Found user: {$user->name}\n\n";

// Get all active sessions
$activeSessions = ExamSession::where('user_id', $user->id)
    ->where('is_active', true)
    ->with('subject')
    ->get();

if ($activeSessions->count() > 0) {
    echo "🔧 Cleaning up {$activeSessions->count()} active session(s):\n\n";
    
    foreach ($activeSessions as $session) {
        $subjectName = $session->subject->name ?? 'Unknown Subject';
        
        // Mark as completed (auto-submitted)
        $session->markAsCompleted(true);
        
        echo "✅ Cleaned up: {$subjectName}\n";
    }
    
    echo "\n✅ All sessions cleaned up successfully!\n";
    echo "🔄 Now refresh your dashboard - all subjects should show as 'Pending'\n\n";
    
} else {
    echo "ℹ️ No active sessions found\n";
}

echo "🎯 RESULT: All subjects will now show as 'Pending' instead of 'In Progress'\n";
?>