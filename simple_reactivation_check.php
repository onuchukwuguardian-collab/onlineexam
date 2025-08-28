<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ReactivationRequest;
use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;
use App\Models\User;
use App\Models\Subject;

echo "🔍 REACTIVATION REQUEST DIAGNOSTIC\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Check current status
$pendingRequests = ReactivationRequest::where('status', 'pending')->count();
$totalRequests = ReactivationRequest::count();
$activeBans = ExamBan::where('is_active', true)->count();

echo "📊 CURRENT STATUS:\n";
echo "• Pending reactivation requests: {$pendingRequests}\n";
echo "• Total reactivation requests: {$totalRequests}\n"; 
echo "• Active bans: {$activeBans}\n\n";

if ($pendingRequests > 0) {
    echo "✅ FOUND PENDING REQUESTS:\n";
    $requests = ReactivationRequest::with(['user', 'subject'])
        ->where('status', 'pending')
        ->get();
    
    foreach ($requests as $request) {
        echo "• {$request->user->name} requesting reactivation for {$request->subject->name}\n";
        echo "  Status: {$request->status} | Requested: {$request->requested_at}\n\n";
    }
    
    echo "🎯 These requests SHOULD appear in admin dashboard at:\n";
    echo "   /admin/security (Reactivation Requests tab)\n\n";
    
} else {
    echo "❌ NO PENDING REQUESTS FOUND\n\n";
    
    if ($activeBans > 0) {
        echo "💡 Found {$activeBans} active bans. Creating test reactivation request...\n";
        
        // Get first active ban
        $ban = ExamBan::with(['user', 'subject'])->where('is_active', true)->first();
        
        if ($ban) {
            echo "Creating request for: {$ban->user->name} - {$ban->subject->name}\n";
            
            try {
                $request = ReactivationRequest::create([
                    'user_id' => $ban->user_id,
                    'subject_id' => $ban->subject_id, 
                    'exam_ban_id' => $ban->id,
                    'request_message' => 'This is a test reactivation request created by diagnostic script. Please approve this to test the admin dashboard functionality.',
                    'status' => 'pending',
                    'requested_at' => now(),
                    'ban_count' => 1
                ]);
                
                echo "✅ Test reactivation request created (ID: {$request->id})\n";
                echo "🎯 Now check admin dashboard: /admin/security\n";
                echo "   You should see this request in the 'Reactivation Requests' tab\n\n";
                
            } catch (\Exception $e) {
                echo "❌ Failed to create test request: " . $e->getMessage() . "\n\n";
            }
        } else {
            echo "❌ No active bans found to create test request\n\n";
        }
    } else {
        echo "ℹ️  No active bans found. System is clean.\n";
        echo "   To test reactivation requests:\n";
        echo "   1. Create a violation (student switches tabs during exam)\n";
        echo "   2. Student gets banned\n";
        echo "   3. Student submits reactivation request\n";
        echo "   4. Admin can then see and approve/reject it\n\n";
    }
}

echo "🔚 DIAGNOSTIC COMPLETE\n";