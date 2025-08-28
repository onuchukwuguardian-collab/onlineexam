<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\ExamBan;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔍 CHECKING BANNED STUDENTS IN DATABASE\n";
echo "======================================\n\n";

// Get all bans
$allBans = ExamBan::with(['user', 'subject'])->get();
echo "📊 Total bans in database: " . $allBans->count() . "\n\n";

foreach ($allBans as $ban) {
    echo "Ban ID: {$ban->id}\n";
    echo "Student: " . ($ban->user->name ?? 'Unknown') . "\n";
    echo "Subject: " . ($ban->subject->name ?? 'Unknown') . "\n";
    echo "Is Active: " . ($ban->is_active ? 'YES' : 'NO') . "\n";
    echo "Banned At: {$ban->banned_at}\n";
    echo "Total Violations: {$ban->total_violations}\n";
    echo "---\n";
}

// Check what the controller query returns
echo "\n🔍 CHECKING CONTROLLER QUERY RESULTS\n";
echo "====================================\n";

$controllerQuery = ExamBan::with(['user', 'subject'])
    ->where('is_active', true)
    ->orderBy('banned_at', 'desc')
    ->get();

echo "📊 Active bans (controller query): " . $controllerQuery->count() . "\n\n";

foreach ($controllerQuery as $ban) {
    echo "Active Ban ID: {$ban->id}\n";
    echo "Student: " . ($ban->user->name ?? 'Unknown') . "\n";
    echo "Subject: " . ($ban->subject->name ?? 'Unknown') . "\n";
    echo "Banned At: {$ban->banned_at}\n";
    echo "---\n";
}

echo "\nTest completed! ✨\n";