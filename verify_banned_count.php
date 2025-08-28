<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamBan;

echo "🔍 VERIFYING BANNED STUDENTS COUNT\n";
echo "==================================\n\n";

// Get all active banned students
$activeBans = ExamBan::with(['user', 'subject'])
    ->where('is_active', true)
    ->get();

echo "📊 Active banned students: " . $activeBans->count() . "\n";
echo "Expected count: 2\n\n";

if ($activeBans->count() > 0) {
    echo "🚨 ACTIVE BANNED STUDENTS:\n";
    echo "=========================\n";
    
    foreach ($activeBans as $index => $ban) {
        echo ($index + 1) . ". Ban ID: {$ban->id}\n";
        echo "   Student: {$ban->user->name}\n";
        echo "   Email: {$ban->user->email}\n";
        echo "   Subject: {$ban->subject->name}\n";
        echo "   Banned At: {$ban->banned_at}\n";
        echo "   Is Active: " . ($ban->is_active ? 'TRUE' : 'FALSE') . "\n";
        echo "   Ban Reason: " . substr($ban->ban_reason, 0, 80) . "...\n";
        echo "   ---\n";
    }
} else {
    echo "❌ No active banned students found!\n";
}

// Also check ALL ban records to find Emeka
echo "\n📋 ALL BAN RECORDS (INCLUDING INACTIVE):\n";
echo "=========================================\n";

$allBans = ExamBan::with(['user', 'subject'])->get();

foreach ($allBans as $index => $ban) {
    echo ($index + 1) . ". Ban ID: {$ban->id}\n";
    echo "   Student: {$ban->user->name}\n";
    echo "   Subject: {$ban->subject->name}\n";
    echo "   Is Active: " . ($ban->is_active ? 'TRUE' : 'FALSE') . "\n";
    echo "   Banned At: {$ban->banned_at}\n";
    if (!$ban->is_active && $ban->reactivated_at) {
        echo "   Reactivated At: {$ban->reactivated_at}\n";
    }
    echo "   ---\n";
}

echo "\nTotal ban records: " . $allBans->count() . "\n";
echo "Active bans: " . $allBans->where('is_active', true)->count() . "\n";
echo "Inactive bans: " . $allBans->where('is_active', false)->count() . "\n";

echo "\nTest completed! ✨\n";