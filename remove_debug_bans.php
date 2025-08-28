<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;
use Illuminate\Support\Facades\DB;

echo "=== REMOVING DEBUG BAN DATA FOR PRODUCTION ===\n";
echo "Cleaning up test/debug ban records...\n\n";

DB::beginTransaction();
try {
    // Remove debug bans
    $debugBans = ExamBan::where('ban_reason', 'like', '%debug%')
        ->orWhere('ban_reason', 'like', '%test%')
        ->orWhere('ban_reason', 'like', '%REACTIVATED TEST BAN%')
        ->get();
    
    echo "Found " . $debugBans->count() . " debug ban records\n";
    
    foreach ($debugBans as $ban) {
        echo "Removing debug ban: User ID {$ban->user_id}, Subject ID {$ban->subject_id}, Reason: {$ban->ban_reason}\n";
        $ban->delete();
    }
    
    // Remove debug security violations
    $debugViolations = ExamSecurityViolation::where('description', 'like', '%debug%')
        ->orWhere('description', 'like', '%test%')
        ->get();
    
    echo "\nFound " . $debugViolations->count() . " debug violation records\n";
    
    foreach ($debugViolations as $violation) {
        echo "Removing debug violation: User ID {$violation->user_id}, Type: {$violation->violation_type}\n";
        $violation->delete();
    }
    
    DB::commit();
    
    echo "\n✅ Successfully removed all debug/test ban data!\n";
    echo "🚀 System is now clean for production deployment.\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Error removing debug data: " . $e->getMessage() . "\n";
}