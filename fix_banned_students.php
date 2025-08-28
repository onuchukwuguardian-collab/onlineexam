<?php

require __DIR__ . '/vendor/autoload.php';
use App\Models\ExamBan;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔄 REACTIVATING ALL INACTIVE BANS\n";
echo "=================================\n\n";

$inactiveBans = ExamBan::where('is_active', false)->get();
echo "📊 Found {$inactiveBans->count()} inactive bans to reactivate\n\n";

foreach ($inactiveBans as $ban) {
    $ban->update([
        'is_active' => true,
        'reactivated_at' => null,
        'reactivated_by' => null,
        'reactivation_reason' => null,
        'admin_notes' => null
    ]);
    
    echo "✅ Reactivated Ban ID: {$ban->id} - {$ban->user->name} - {$ban->subject->name}\n";
}

$activeBans = ExamBan::where('is_active', true)->count();
echo "\n📊 Total active bans now: {$activeBans}\n\n";

echo "📋 ALL ACTIVE BANS:\n";
echo "==================\n";
$allActive = ExamBan::with(['user', 'subject'])->where('is_active', true)->get();
foreach ($allActive as $ban) {
    echo "- Ban ID {$ban->id}: {$ban->user->name} banned from {$ban->subject->name}\n";
}

echo "\n✅ Now all banned students should appear in admin dashboard!\n";
echo "🔗 Check: http://web-portal.test/admin/security/banned-students\n";