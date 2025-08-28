<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        api: __DIR__.'/routes/api.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Import models
use App\Models\ExamBan;

echo "🔍 CHECKING ACTIVE BANNED STUDENTS\n";
echo "=================================\n\n";

// Get all bans
$allBans = ExamBan::with(['user', 'subject'])->get();
echo "📊 Total bans in database: " . $allBans->count() . "\n\n";

// Get active bans only
$activeBans = ExamBan::with(['user', 'subject'])
    ->where('is_active', true)
    ->orderBy('banned_at', 'desc')
    ->get();

echo "🚨 ACTIVE BANNED STUDENTS (is_active = true): " . $activeBans->count() . "\n";
echo "==============================================\n";

if ($activeBans->count() > 0) {
    foreach ($activeBans as $ban) {
        echo "Ban ID: {$ban->id}\n";
        echo "Student: {$ban->user->name}\n";
        echo "Email: {$ban->user->email}\n";
        echo "Registration: {$ban->user->registration_number}\n";
        echo "Subject: {$ban->subject->name}\n";
        echo "Banned At: {$ban->banned_at}\n";
        echo "Total Violations: {$ban->total_violations}\n";
        echo "Ban Reason: {$ban->ban_reason}\n";
        echo "---\n";
    }
} else {
    echo "✅ No currently active banned students found.\n";
    echo "This means all previously banned students have been reactivated.\n\n";
}

// Get inactive bans (reactivated)
$inactiveBans = ExamBan::with(['user', 'subject'])
    ->where('is_active', false)
    ->orderBy('banned_at', 'desc')
    ->get();

echo "\n📋 REACTIVATED STUDENTS (is_active = false): " . $inactiveBans->count() . "\n";
echo "===========================================\n";

if ($inactiveBans->count() > 0) {
    foreach ($inactiveBans as $ban) {
        echo "Ban ID: {$ban->id}\n";
        echo "Student: {$ban->user->name}\n";
        echo "Subject: {$ban->subject->name}\n";
        echo "Originally Banned: {$ban->banned_at}\n";
        if ($ban->reactivated_at) {
            echo "Reactivated: {$ban->reactivated_at}\n";
            echo "Reactivation Reason: {$ban->reactivation_reason}\n";
        }
        echo "---\n";
    }
}

echo "\n🎯 SUMMARY:\n";
echo "- Active bans (will show in admin dashboard): {$activeBans->count()}\n";
echo "- Reactivated bans (will NOT show in admin dashboard): {$inactiveBans->count()}\n";
echo "- Total ban records: {$allBans->count()}\n\n";

echo "🌐 Admin Dashboard URL: http://web-portal.test/admin/security/banned-students\n";
echo "📝 Only students with 'is_active = true' will appear in the banned students table.\n\n";

echo "Test completed! ✨\n";