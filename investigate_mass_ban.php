<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ExamBan;
use App\Models\Subject;
use App\Models\User;

echo "🚨 INVESTIGATING MASS BANNING ISSUE\n";
echo "==================================\n\n";

// Find Basic Science subject
$basicScience = Subject::where('name', 'like', '%Basic Science%')->first();

if (!$basicScience) {
    echo "❌ Basic Science subject not found!\n";
    exit;
}

echo "📚 Subject: {$basicScience->name} (ID: {$basicScience->id})\n\n";

// Get all active bans for Basic Science
$bans = ExamBan::with('user')
    ->where('subject_id', $basicScience->id)
    ->where('is_active', true)
    ->get();

echo "🚫 Active bans for Basic Science: {$bans->count()}\n";
echo "=" . str_repeat("=", 50) . "\n\n";

if ($bans->count() === 0) {
    echo "✅ NO ACTIVE BANS FOUND - This contradicts your report!\n";
    echo "Maybe the issue is elsewhere?\n\n";
    
    // Check for inactive bans
    $inactiveBans = ExamBan::with('user')
        ->where('subject_id', $basicScience->id)
        ->where('is_active', false)
        ->get();
    
    echo "📋 Inactive bans (reactivated): {$inactiveBans->count()}\n";
    if ($inactiveBans->count() > 0) {
        echo "Recent reactivations:\n";
        foreach ($inactiveBans->take(5) as $ban) {
            echo "- {$ban->user->name} (reactivated: {$ban->reactivated_at})\n";
        }
    }
} else {
    echo "⚠️ FOUND MULTIPLE BANS - Analyzing each one:\n\n";
    
    foreach ($bans as $index => $ban) {
        $banNumber = $index + 1;
        echo "Ban #{$banNumber}:\n";
        echo "  👤 User: {$ban->user->name} (ID: {$ban->user_id})\n";
        echo "  📧 Email: {$ban->user->email}\n";
        echo "  🆔 Registration: " . ($ban->user->registration_number ?? 'Not set') . "\n";
        echo "  📅 Banned at: {$ban->banned_at}\n";
        echo "  🔢 Total violations: {$ban->total_violations}\n";
        echo "  📝 Ban reason: " . substr($ban->ban_reason, 0, 100) . "...\n";
        echo "  🔗 Ban ID: {$ban->id}\n";
        echo "  " . str_repeat("-", 60) . "\n\n";
    }
}

// Check if there are duplicate users
$uniqueUsers = $bans->pluck('user_id')->unique();
echo "👥 Unique users banned: {$uniqueUsers->count()}\n";
echo "🔢 Total ban records: {$bans->count()}\n";

if ($uniqueUsers->count() < $bans->count()) {
    echo "⚠️ DUPLICATE BANS DETECTED - Same user banned multiple times!\n";
    
    // Find duplicates
    $userCounts = $bans->groupBy('user_id');
    foreach ($userCounts as $userId => $userBans) {
        if ($userBans->count() > 1) {
            $user = $userBans->first()->user;
            echo "❌ {$user->name} has {$userBans->count()} active bans for Basic Science!\n";
        }
    }
}

// Check total students vs banned students
$totalStudents = User::where('role', 'student')->count();
echo "\n📊 COMPARISON:\n";
echo "Total students in system: {$totalStudents}\n";
echo "Students banned from Basic Science: {$uniqueUsers->count()}\n";
echo "Percentage banned: " . round(($uniqueUsers->count() / $totalStudents) * 100, 2) . "%\n\n";

if ($uniqueUsers->count() == $totalStudents) {
    echo "🚨 CRITICAL: ALL STUDENTS ARE BANNED FROM BASIC SCIENCE!\n";
    echo "This confirms the mass-banning bug you reported.\n\n";
} elseif ($uniqueUsers->count() > ($totalStudents * 0.5)) {
    echo "⚠️ WARNING: More than 50% of students are banned!\n";
    echo "This suggests a systemic issue.\n\n";
}

echo "✅ Investigation complete!\n";