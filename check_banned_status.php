<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamBan;

echo "🔍 CHECKING BANNED STUDENTS DATABASE STATUS\n";
echo "==========================================\n\n";

// Get all bans
$allBans = ExamBan::with(['user', 'subject'])->get();
echo "📊 Total ban records in database: " . $allBans->count() . "\n\n";

// Get active bans (should show in admin dashboard)
$activeBans = ExamBan::with(['user', 'subject'])->where('is_active', true)->get();
echo "🚨 ACTIVE BANNED STUDENTS (is_active = true): " . $activeBans->count() . "\n";
echo "These should appear in /admin/security/banned-students\n";
echo "================================================\n";

if ($activeBans->count() > 0) {
    foreach ($activeBans as $index => $ban) {
        echo ($index + 1) . ". Ban ID: {$ban->id}\n";
        echo "   Student: " . ($ban->user ? $ban->user->name : 'USER MISSING') . "\n";
        echo "   Email: " . ($ban->user ? $ban->user->email : 'USER MISSING') . "\n";
        echo "   Subject: " . ($ban->subject ? $ban->subject->name : 'SUBJECT MISSING') . "\n";
        echo "   Banned At: {$ban->banned_at}\n";
        echo "   Is Active: " . ($ban->is_active ? 'TRUE' : 'FALSE') . "\n";
        echo "   Ban Reason: " . substr($ban->ban_reason, 0, 50) . "...\n";
        echo "   ---\n";
    }
} else {
    echo "❌ NO ACTIVE BANNED STUDENTS FOUND!\n";
    echo "This explains why the admin dashboard shows 'no banned students'\n\n";
}

// Get inactive bans
$inactiveBans = ExamBan::with(['user', 'subject'])->where('is_active', false)->get();
echo "\n📋 INACTIVE BANNED STUDENTS (is_active = false): " . $inactiveBans->count() . "\n";
echo "These are reactivated students who won't show in admin dashboard\n";
echo "==============================================================\n";

if ($inactiveBans->count() > 0) {
    foreach ($inactiveBans as $index => $ban) {
        echo ($index + 1) . ". Ban ID: {$ban->id}\n";
        echo "   Student: " . ($ban->user ? $ban->user->name : 'USER MISSING') . "\n";
        echo "   Subject: " . ($ban->subject ? $ban->subject->name : 'SUBJECT MISSING') . "\n";
        echo "   Originally Banned: {$ban->banned_at}\n";
        echo "   Reactivated: " . ($ban->reactivated_at ? $ban->reactivated_at : 'NOT SET') . "\n";
        echo "   Is Active: " . ($ban->is_active ? 'TRUE' : 'FALSE') . "\n";
        echo "   ---\n";
    }
}

echo "\n🎯 DIAGNOSIS:\n";
echo "=============\n";
if ($activeBans->count() == 0 && $inactiveBans->count() > 0) {
    echo "❌ PROBLEM: All banned students have been reactivated (is_active = false)\n";
    echo "💡 SOLUTION: Students need to be banned again to appear in admin dashboard\n";
    echo "📝 NOTE: Only students with is_active = true appear in banned students page\n";
} elseif ($activeBans->count() == 0 && $allBans->count() == 0) {
    echo "❌ PROBLEM: No ban records exist in database\n";
    echo "💡 SOLUTION: Students need to be banned first\n";
} elseif ($activeBans->count() > 0) {
    echo "✅ GOOD: There are active banned students\n";
    echo "🔍 CHECK: Verify admin authentication and page permissions\n";
}

echo "\n🌐 Admin Dashboard URL: http://web-portal.test/admin/security/banned-students\n";
echo "✨ Check completed!\n";