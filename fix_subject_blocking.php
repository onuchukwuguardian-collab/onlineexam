<?php
/**
 * Fix All Subject Blocking and Unknown Subject Issues
 * This script addresses multiple security violation issues
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
use App\Models\ReactivationRequest;
use Carbon\Carbon;

echo "🔧 FIXING ALL SUBJECT BLOCKING AND UNKNOWN SUBJECT ISSUES\n";
echo "=========================================================\n\n";

// Get user by email
$user = User::where('email', 'john.ade@example.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit;
}

echo "✅ Found user: {$user->name} (ID: {$user->id})\n\n";

// Step 1: Check all active bans
echo "🚫 STEP 1: CHECKING ALL ACTIVE BANS\n";
echo "===================================\n";

$activeBans = ExamBan::where('user_id', $user->id)
    ->where('is_active', true)
    ->with('subject')
    ->get();

if ($activeBans->count() > 0) {
    echo "❌ Found {$activeBans->count()} active ban(s) blocking subjects:\n\n";
    
    foreach ($activeBans as $ban) {
        $subjectName = $ban->subject->name ?? 'Unknown Subject';
        echo "  📖 Subject: {$subjectName}\n";
        echo "     Ban ID: {$ban->id}\n";
        echo "     Reason: {$ban->ban_reason}\n";
        echo "     Banned: {$ban->banned_at}\n";
        echo "     Violations: {$ban->total_violations}\n\n";
    }
    
    echo "🤔 What would you like to do with these bans?\n";
    echo "1. Remove ALL bans (make all subjects available)\n";
    echo "2. Keep bans but create reactivation requests\n";
    echo "3. Review each ban individually\n";
    echo "4. Skip ban handling\n";
    echo "Enter choice (1-4): ";
    
    $handle = fopen("php://stdin", "r");
    $choice = trim(fgets($handle));
    fclose($handle);
    
    switch ($choice) {
        case '1':
            foreach ($activeBans as $ban) {
                $ban->update(['is_active' => false]);
                echo "✅ Deactivated ban for: " . ($ban->subject->name ?? 'Unknown') . "\n";
            }
            echo "\n✅ All bans removed - all subjects now available!\n\n";
            break;
            
        case '2':
            foreach ($activeBans as $ban) {
                $subjectName = $ban->subject->name ?? 'Unknown Subject';
                
                // Check if reactivation request already exists
                $existingRequest = ReactivationRequest::where('user_id', $user->id)
                    ->where('subject_id', $ban->subject_id)
                    ->where('status', 'pending')
                    ->first();
                    
                if (!$existingRequest) {
                    $request = ReactivationRequest::create([
                        'user_id' => $user->id,
                        'subject_id' => $ban->subject_id,
                        'exam_ban_id' => $ban->id,
                        'request_message' => 'Automated reactivation request created by diagnostic script. Please review and approve access for this subject.',
                        'status' => 'pending',
                        'requested_at' => now()
                    ]);
                    echo "✅ Created reactivation request for: {$subjectName}\n";
                } else {
                    echo "ℹ️ Reactivation request already exists for: {$subjectName}\n";
                }
            }
            echo "\n✅ Reactivation requests created - notify admin to approve!\n\n";
            break;
            
        case '3':
            foreach ($activeBans as $ban) {
                $subjectName = $ban->subject->name ?? 'Unknown Subject';
                echo "\n🔍 Ban for '{$subjectName}':\n";
                echo "   Reason: {$ban->ban_reason}\n";
                echo "   Violations: {$ban->total_violations}\n";
                echo "   Date: {$ban->banned_at}\n";
                echo "\nRemove this ban? (y/n): ";
                
                $handle = fopen("php://stdin", "r");
                $response = trim(fgets($handle));
                fclose($handle);
                
                if (strtolower($response) === 'y') {
                    $ban->update(['is_active' => false]);
                    echo "✅ Removed ban for: {$subjectName}\n";
                } else {
                    echo "⏩ Kept ban for: {$subjectName}\n";
                }
            }
            break;
            
        default:
            echo "⏩ Skipping ban handling\n\n";
            break;
    }
} else {
    echo "✅ No active bans found\n\n";
}

// Step 2: Fix "Unknown Subject" issue by ensuring proper redirects
echo "🛠️ STEP 2: FIXING 'UNKNOWN SUBJECT' ISSUE\n";
echo "==========================================\n";

// Get recent violations to identify subjects
$recentViolations = ExamSecurityViolation::where('user_id', $user->id)
    ->with('subject')
    ->orderBy('occurred_at', 'desc')
    ->take(10)
    ->get();

if ($recentViolations->count() > 0) {
    echo "📋 Found {$recentViolations->count()} recent violation(s):\n\n";
    
    foreach ($recentViolations as $violation) {
        $subjectName = $violation->subject->name ?? 'Unknown Subject';
        echo "  📖 {$subjectName}: {$violation->violation_type}\n";
        echo "     Date: {$violation->occurred_at}\n";
        echo "     Description: " . substr($violation->description, 0, 60) . "...\n\n";
    }
    
    // Check the most recent violation for subject context
    $mostRecentViolation = $recentViolations->first();
    if ($mostRecentViolation && $mostRecentViolation->subject) {
        echo "✅ Most recent violation was in: {$mostRecentViolation->subject->name}\n";
        echo "   This subject should be used for violation-detected page\n\n";
    }
} else {
    echo "ℹ️ No recent violations found\n\n";
}

// Step 3: Test violation-detected page access
echo "🧪 STEP 3: TESTING VIOLATION-DETECTED PAGE ACCESS\n";
echo "=================================================\n";

// Try to access violation-detected page with different parameters
$testUrls = [
    '/security/violation-detected',
    '/security/violation-detected?subject_id=1',
    '/security/violation-detected?subject_id=2',
    '/security/violation-detected?subject_id=3',
    '/security/violation-detected?subject_id=4'
];

echo "🔗 You should test these URLs in your browser:\n\n";

foreach ($testUrls as $url) {
    echo "   {$url}\n";
}

echo "\n💡 The URL with subject_id parameter should show the correct subject name\n";
echo "   instead of 'Unknown Subject'\n\n";

// Step 4: Clear any problematic data
echo "🧹 STEP 4: CLEANING UP PROBLEMATIC DATA\n";
echo "=======================================\n";

// Clean up any test violations that might be confusing the system
$testViolations = ExamSecurityViolation::where('user_id', $user->id)
    ->where('description', 'like', '%test%')
    ->orWhere('description', 'like', '%TEST%')
    ->get();

if ($testViolations->count() > 0) {
    echo "🧪 Found {$testViolations->count()} test violation(s)\n";
    echo "Clean up test violations? (y/n): ";
    
    $handle = fopen("php://stdin", "r");
    $cleanup = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($cleanup) === 'y') {
        foreach ($testViolations as $violation) {
            $violation->delete();
        }
        echo "✅ Cleaned up test violations\n\n";
    } else {
        echo "⏩ Kept test violations\n\n";
    }
} else {
    echo "✅ No test violations to clean up\n\n";
}

// Step 5: Final status check
echo "📊 STEP 5: FINAL STATUS CHECK\n";
echo "=============================\n";

$currentBans = ExamBan::where('user_id', $user->id)
    ->where('is_active', true)
    ->count();

$pendingRequests = ReactivationRequest::where('user_id', $user->id)
    ->where('status', 'pending')
    ->count();

echo "🚫 Active bans: {$currentBans}\n";
echo "📋 Pending reactivation requests: {$pendingRequests}\n";

if ($currentBans === 0) {
    echo "✅ SUCCESS: No active bans - all subjects should be available!\n";
} else {
    echo "⚠️ WARNING: {$currentBans} ban(s) still active - subjects may be blocked\n";
}

if ($pendingRequests > 0) {
    echo "📩 INFO: {$pendingRequests} reactivation request(s) waiting for admin approval\n";
}

echo "\n🎯 NEXT STEPS:\n";
echo "=============\n";
echo "1. Refresh your dashboard page\n";
echo "2. Try accessing an exam - should work if bans removed\n";
echo "3. If you see 'Unknown Subject', try the URL with ?subject_id=X\n";
echo "4. Contact admin to approve any pending reactivation requests\n";
echo "5. Clear browser cache if issues persist\n\n";

echo "✅ Diagnostic and fix complete!\n";
?>