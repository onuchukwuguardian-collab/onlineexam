<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ReactivationRequest;
use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;

echo "🔍 REACTIVATION SYSTEM STATUS DIAGNOSTIC\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Basic counts
$pendingRequests = ReactivationRequest::where('status', 'pending')->count();
$totalRequests = ReactivationRequest::count();
$activeBans = ExamBan::where('is_active', true)->count();
$totalViolations = ExamSecurityViolation::count();

echo "📊 SYSTEM OVERVIEW:\n";
echo "• Pending reactivation requests: {$pendingRequests}\n";
echo "• Total reactivation requests: {$totalRequests}\n"; 
echo "• Active bans: {$activeBans}\n";
echo "• Total violations: {$totalViolations}\n\n";

if ($pendingRequests > 0) {
    echo "✅ PENDING REQUESTS FOUND:\n";
    $requests = ReactivationRequest::with(['user', 'subject'])->where('status', 'pending')->get();
    foreach ($requests as $request) {
        echo "• {$request->user->name} for {$request->subject->name} (ID: {$request->id})\n";
        echo "  Requested: {$request->requested_at}\n";
        echo "  Status: {$request->status}\n\n";
    }
    
    echo "🎯 ADMIN SHOULD SEE THESE REQUESTS IN:\n";
    echo "• Main dashboard: /admin/security (Reactivation Requests tab)\n";
    echo "• Full page: /admin/security/reactivation-requests\n\n";
} else {
    echo "❌ NO PENDING REQUESTS\n";
    echo "This is why admin dashboard shows 'No Pending Requests'\n\n";
}

if ($activeBans > 0) {
    echo "📋 ACTIVE BANS (Students who can request reactivation):\n";
    $bans = ExamBan::with(['user', 'subject'])->where('is_active', true)->take(5)->get();
    foreach ($bans as $ban) {
        echo "• {$ban->user->name} banned from {$ban->subject->name}\n";
    }
    
    if ($pendingRequests === 0) {
        echo "\n💡 TIP: These banned students can create reactivation requests at:\n";
        echo "   /student/reactivation (after logging in)\n";
    }
} else {
    echo "✅ NO ACTIVE BANS - System is clean\n";
}

echo "\n🔚 DIAGNOSTIC COMPLETE\n";