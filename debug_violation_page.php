<?php
/**
 * Debug Violation Page Issues
 * This script tests the exact same logic as the SecurityViewController
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

echo "🔍 DEBUGGING VIOLATION PAGE ISSUES\n";
echo "==================================\n\n";

// Get user by email
$user = User::where('email', 'john.ade@example.com')->first();

if (!$user) {
    echo "❌ User not found\n";
    exit;
}

echo "✅ Found user: {$user->name} (ID: {$user->id})\n\n";

// Test the exact same logic as SecurityViewController::violationDetected()
function testViolationPageLogic($user, $requestSubjectId = null) {
    echo "🧪 Testing violation page logic with subject_id: " . ($requestSubjectId ?? 'null') . "\n";
    echo "===========================================================================\n";
    
    $subject = null;
    $ban = null;
    $subjectId = $requestSubjectId;
    
    // Method 1: Direct subject_id parameter
    if ($subjectId) {
        $subject = Subject::find($subjectId);
        echo "Method 1 - Direct parameter: " . ($subject ? "✅ Found {$subject->name}" : "❌ No subject found") . "\n";
    }
    
    // Method 2: Find active ban with subject relationship
    $ban = ExamBan::with('subject')
        ->where('user_id', $user->id)
        ->where('is_active', true)
        ->when($subjectId, function($query) use ($subjectId) {
            return $query->where('subject_id', $subjectId);
        })
        ->orderBy('banned_at', 'desc')
        ->first();
        
    if ($ban && $ban->subject && !$subject) {
        $subject = $ban->subject;
        $subjectId = $ban->subject_id;
        echo "Method 2 - Active ban: ✅ Found {$subject->name} (Ban ID: {$ban->id})\n";
    } else {
        echo "Method 2 - Active ban: " . ($ban ? "❌ Ban found but no subject" : "❌ No active ban") . "\n";
    }
    
    // Method 3: Recent violation for specific subject
    if (!$subject && $subjectId) {
        $recentViolation = ExamSecurityViolation::with('subject')
            ->where('user_id', $user->id)
            ->where('subject_id', $subjectId)
            ->orderBy('occurred_at', 'desc')
            ->first();
            
        if ($recentViolation && $recentViolation->subject) {
            $subject = $recentViolation->subject;
            echo "Method 3 - Recent violation (specific): ✅ Found {$subject->name}\n";
        } else {
            echo "Method 3 - Recent violation (specific): ❌ No violation found\n";
        }
    }
    
    // Method 4: Any recent violation to get ANY subject context
    if (!$subject) {
        $anyRecentViolation = ExamSecurityViolation::with('subject')
            ->where('user_id', $user->id)
            ->orderBy('occurred_at', 'desc')
            ->first();
            
        if ($anyRecentViolation && $anyRecentViolation->subject) {
            $subject = $anyRecentViolation->subject;
            $subjectId = $subject->id;
            echo "Method 4 - Any recent violation: ✅ Found {$subject->name} (ID: {$subject->id})\n";
        } else {
            echo "Method 4 - Any recent violation: ❌ No violations found\n";
        }
    }
    
    // Method 5: Use first available subject if still no context
    if (!$subject && $user->class_id) {
        $subject = Subject::where('class_id', $user->class_id)->first();
        if ($subject) {
            $subjectId = $subject->id;
            echo "Method 5 - First available: ✅ Found {$subject->name} (ID: {$subject->id})\n";
        } else {
            echo "Method 5 - First available: ❌ No subjects in class\n";
        }
    }
    
    echo "\n📊 FINAL RESULT:\n";
    echo "  Subject ID: " . ($subjectId ?? 'NULL') . "\n";
    echo "  Subject Name: " . ($subject ? $subject->name : 'Unknown Subject') . "\n";
    echo "  Ban Found: " . ($ban ? "Yes (ID: {$ban->id})" : 'No') . "\n\n";
    
    return ['subject' => $subject, 'ban' => $ban, 'subjectId' => $subjectId];
}

// Test without subject_id parameter (current failing scenario)
echo "🔴 TEST 1: No subject_id parameter (your current situation)\n";
$result1 = testViolationPageLogic($user);

// Test with subject_id parameter
echo "🔵 TEST 2: With subject_id=1 parameter\n";
$result2 = testViolationPageLogic($user, 1);

// Check what subjects are available
echo "📚 AVAILABLE SUBJECTS FOR USER'S CLASS:\n";
echo "=======================================\n";
$availableSubjects = Subject::where('class_id', $user->class_id)->get();
if ($availableSubjects->count() > 0) {
    foreach ($availableSubjects as $subj) {
        echo "  ID: {$subj->id} - {$subj->name}\n";
    }
} else {
    echo "  ❌ No subjects found for class ID: {$user->class_id}\n";
}

// Check for violations
echo "\n🔍 RECENT VIOLATIONS:\n";
echo "====================\n";
$violations = ExamSecurityViolation::where('user_id', $user->id)
    ->with('subject')
    ->orderBy('occurred_at', 'desc')
    ->take(5)
    ->get();

if ($violations->count() > 0) {
    foreach ($violations as $violation) {
        $subjectName = $violation->subject ? $violation->subject->name : 'Unknown';
        echo "  {$violation->occurred_at} - {$violation->violation_type} - {$subjectName}\n";
    }
} else {
    echo "  ✅ No violations found\n";
}

// Check for bans
echo "\n🚫 ACTIVE BANS:\n";
echo "==============\n";
$bans = ExamBan::where('user_id', $user->id)
    ->where('is_active', true)
    ->with('subject')
    ->get();

if ($bans->count() > 0) {
    foreach ($bans as $ban) {
        $subjectName = $ban->subject ? $ban->subject->name : 'Unknown';
        echo "  Ban ID: {$ban->id} - {$subjectName} - {$ban->ban_reason}\n";
    }
} else {
    echo "  ✅ No active bans found\n";
}

echo "\n💡 RECOMMENDATIONS:\n";
echo "==================\n";

if (!$result1['subject']) {
    echo "❌ PROBLEM: No subject context found when accessing violation page directly\n";
    
    if ($availableSubjects->count() > 0) {
        $firstSubject = $availableSubjects->first();
        echo "🔧 SOLUTION: Access violation page with subject_id parameter:\n";
        echo "   URL: /security/violation-detected?subject_id={$firstSubject->id}\n";
        echo "   This should show: '{$firstSubject->name}' instead of 'Unknown Subject'\n\n";
    }
    
    if ($violations->count() > 0) {
        echo "🔧 ALTERNATIVE: There are violations but no ban records\n";
        echo "   This suggests the ban creation process might be broken\n";
        echo "   The controller should create bans from violations\n\n";
    }
    
    if ($violations->count() === 0) {
        echo "🤔 STRANGE: No violations found but user sees violation page\n";
        echo "   This suggests user was redirected here incorrectly\n";
        echo "   Check how they reached this page\n\n";
    }
}

echo "🌐 TEST URLS TO TRY:\n";
echo "===================\n";
echo "1. /security/violation-detected\n";
if ($availableSubjects->count() > 0) {
    foreach ($availableSubjects as $subj) {
        echo "2. /security/violation-detected?subject_id={$subj->id}\n";
    }
}

echo "\n✅ Debug complete!\n";
?>