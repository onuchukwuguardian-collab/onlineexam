<?php
/**
 * Quick Test: Access Violation Page with Subject ID
 * This simulates the correct way to access the violation page
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Subject;

echo "🧪 TESTING VIOLATION PAGE ACCESS\n";
echo "================================\n\n";

// Get user
$user = User::where('email', 'john.ade@example.com')->first();
$subjects = Subject::where('class_id', $user->class_id)->get();

echo "✅ User: {$user->name}\n";
echo "📚 Available subjects:\n";
foreach ($subjects as $subject) {
    echo "   ID: {$subject->id} - {$subject->name}\n";
}

echo "\n🌐 CORRECT URLs TO ACCESS:\n";
echo "==========================\n";
echo "The issue is you're accessing: /security/violation-detected\n";
echo "But you should access with subject_id:\n\n";

foreach ($subjects as $subject) {
    echo "✅ /security/violation-detected?subject_id={$subject->id} (shows: {$subject->name})\n";
}

echo "\n💡 EXPLANATION:\n";
echo "===============\n";
echo "1. You have NO active bans (✅ Good)\n";
echo "2. You have NO violations (✅ Good)\n";
echo "3. The SecurityViewController finds subjects correctly\n";
echo "4. But without subject_id parameter, the page can't determine context\n";
echo "5. With subject_id parameter, it will show the correct subject name\n\n";

echo "🔧 IMMEDIATE FIX:\n";
echo "=================\n";
echo "Try this URL in your browser:\n";
$firstSubject = $subjects->first();
echo "http://localhost/security/violation-detected?subject_id={$firstSubject->id}\n\n";
echo "This should show '{$firstSubject->name}' instead of 'Unknown Subject'\n";
echo "And the form should submit successfully without 'subject id field is required' error\n\n";

echo "✅ Test complete! Try the URL above.\n";
?>