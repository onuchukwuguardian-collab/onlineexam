<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamBan;
use App\Models\User;
use App\Models\Subject;

echo "CLEANING UP BANNED STUDENTS DATA\n";
echo "================================\n\n";

// Get ALL active bans
$activeBans = ExamBan::with(['user', 'subject'])->where('is_active', true)->get();
echo "Total active banned students: " . $activeBans->count() . "\n\n";

foreach ($activeBans as $ban) {
    echo "Ban ID: {$ban->id}\n";
    echo "Student: {$ban->user->name} (ID: {$ban->user_id})\n";
    echo "Email: {$ban->user->email}\n";
    echo "Subject: {$ban->subject->name} (ID: {$ban->subject_id})\n";
    echo "Registration: " . ($ban->user->registration_number ?? 'NOT SET') . "\n";
    echo "Banned At: {$ban->banned_at}\n";
    
    // Check if this is the correct ban (Mium John on Biology)
    $isCorrectBan = ($ban->user->name === 'Mium John' && 
                    (strpos(strtolower($ban->subject->name), 'biology') !== false));
    
    echo "Is Correct Ban: " . ($isCorrectBan ? 'YES' : 'NO') . "\n";
    
    if (!$isCorrectBan) {
        echo "ACTION: This ban should be deactivated\n";
        // Deactivate incorrect ban
        $ban->update([
            'is_active' => false,
            'admin_notes' => 'Deactivated - incorrect ban data. Correct ban is Mium John on Biology.',
            'reactivated_at' => now(),
            'reactivation_reason' => 'Data cleanup - incorrect ban record'
        ]);
        echo "✅ DEACTIVATED\n";
    } else {
        echo "✅ KEEPING - This is the correct ban\n";
    }
    
    echo "---\n";
}

// Final check
echo "\nFINAL VERIFICATION:\n";
echo "==================\n";

$finalActiveBans = ExamBan::with(['user', 'subject'])->where('is_active', true)->get();
echo "Final active banned students: " . $finalActiveBans->count() . "\n";

foreach ($finalActiveBans as $ban) {
    echo "✅ {$ban->user->name} - {$ban->subject->name}\n";
}

echo "\n🌐 Now check: http://web-portal.test/admin/security/banned-students\n";
echo "You should only see: Mium John banned on Biology\n";