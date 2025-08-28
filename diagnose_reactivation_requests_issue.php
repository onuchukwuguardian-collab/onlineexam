<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSecurityViolation;
use App\Models\ExamBan;
use App\Models\ReactivationRequest;

echo "🔍 ADMIN DASHBOARD DIAGNOSTIC - REACTIVATION REQUESTS VS 'NO MERCY'\n";
echo "=" . str_repeat("=", 75) . "\n\n";

// Check what admin should see
echo "📊 CURRENT SYSTEM STATUS:\n";
echo "─" . str_repeat("─", 30) . "\n";

$totalViolations = ExamSecurityViolation::count();
$tabSwitchViolations = ExamSecurityViolation::where('violation_type', 'tab_switch')->count();
$activeBans = ExamBan::where('is_active', true)->count();
$pendingRequests = ReactivationRequest::where('status', 'pending')->count();
$allRequests = ReactivationRequest::count();

echo "• Total violations in system: {$totalViolations}\n";
echo "• Tab switch violations: {$tabSwitchViolations}\n";
echo "• Currently banned students: {$activeBans}\n";
echo "• Pending reactivation requests: {$pendingRequests}\n";
echo "• Total reactivation requests: {$allRequests}\n\n";

if ($pendingRequests > 0) {
    echo "✅ GOOD NEWS: There ARE reactivation requests to show!\n\n";
    
    echo "📋 PENDING REACTIVATION REQUESTS:\n";
    echo "─" . str_repeat("─", 40) . "\n";
    
    $requests = ReactivationRequest::with(['user', 'subject'])
        ->where('status', 'pending')
        ->orderBy('requested_at', 'desc')
        ->get();
    
    foreach ($requests as $request) {
        echo "🎓 Student: {$request->user->name}\n";
        echo "   📧 Email: {$request->user->email}\n";
        if ($request->user->registration_number) {
            echo "   🆔 Registration: {$request->user->registration_number}\n";
        }
        echo "   📚 Subject: {$request->subject->name}\n";
        echo "   📅 Requested: {$request->requested_at->format('M d, Y H:i:s')}\n";
        echo "   💬 Message: " . substr($request->request_message, 0, 100) . "...\n";
        echo "   🔗 Direct URL: /admin/security/reactivation-requests/{$request->id}\n";
        echo "   ─" . str_repeat("─", 50) . "\n";
    }
} else {
    echo "❌ NO PENDING REACTIVATION REQUESTS FOUND\n\n";
    echo "💡 This could mean:\n";
    echo "• No students have been banned yet\n";
    echo "• Banned students haven't submitted reactivation requests yet\n";
    echo "• All requests have already been processed\n\n";
}

if ($activeBans > 0) {
    echo "📋 CURRENTLY BANNED STUDENTS:\n";
    echo "─" . str_repeat("─", 40) . "\n";
    
    $bans = ExamBan::with(['user', 'subject'])
        ->where('is_active', true)
        ->orderBy('banned_at', 'desc')
        ->get();
    
    foreach ($bans as $ban) {
        echo "🚫 Student: {$ban->user->name}\n";
        echo "   📧 Email: {$ban->user->email}\n";
        echo "   📚 Subject: {$ban->subject->name}\n";
        echo "   📅 Banned: {$ban->banned_at->format('M d, Y H:i:s')}\n";
        echo "   💬 Reason: {$ban->ban_reason}\n";
        
        // Check if this student has submitted a reactivation request
        $hasRequest = ReactivationRequest::where('user_id', $ban->user_id)
            ->where('subject_id', $ban->subject_id)
            ->where('status', 'pending')
            ->exists();
            
        if ($hasRequest) {
            echo "   ✅ HAS submitted reactivation request\n";
        } else {
            echo "   ❌ Has NOT submitted reactivation request yet\n";
        }
        echo "   ─" . str_repeat("─", 50) . "\n";
    }
} else {
    echo "✅ NO CURRENTLY BANNED STUDENTS\n\n";
}

echo "🎯 ADMIN INSTRUCTIONS - WHERE TO LOOK:\n";
echo "═" . str_repeat("═", 45) . "\n\n";

echo "🔐 Step 1: Login as Admin\n";
$admin = User::where('role', 'admin')->first();
if ($admin) {
    echo "   Use admin email: {$admin->email}\n";
} else {
    echo "   ❌ No admin user found in database!\n";
}

echo "\n🌐 Step 2: Go to Security Dashboard\n";
echo "   Visit: http://your-domain/admin/security\n";

echo "\n📋 Step 3: Look for the RIGHT TAB\n";
echo "   You should see THREE tabs:\n";
echo "   • 📊 Recent Violations (shows all violations)\n";
echo "   • 🚫 Banned Students (shows banned students with UNBAN buttons)\n";
echo "   • 📋 Reactivation Requests (shows student requests with APPROVE/REJECT buttons)\n";

echo "\n⚠️  IMPORTANT: Don't confuse these!\n";
echo "   ❌ 'Banned Students' tab = Direct unban (admin bypasses student request)\n";
echo "   ✅ 'Reactivation Requests' tab = Student requests for admin approval\n";

if ($pendingRequests > 0) {
    echo "\n✅ Step 4: Click 'Reactivation Requests' Tab\n";
    echo "   You should see {$pendingRequests} pending request(s)\n";
    echo "   Each request will have:\n";
    echo "   • Student name and details\n";
    echo "   • Subject they want reactivated for\n";
    echo "   • Their request message\n";
    echo "   • ✅ APPROVE button (green)\n";
    echo "   • ❌ REJECT button (red)\n";
    echo "   • 👁️ VIEW button to see full details\n";
} else {
    echo "\n❌ Step 4: No Requests to Show Yet\n";
    echo "   The 'Reactivation Requests' tab will show:\n";
    echo "   📭 'No Pending Requests - All reactivation requests have been processed'\n";
}

echo "\n🛠️ TROUBLESHOOTING 'NO MERCY' ISSUE:\n";
echo "═" . str_repeat("═", 45) . "\n";

echo "\n❓ If you see 'no mercy' instead of reactivation options:\n";
echo "1. 🔄 Clear browser cache and refresh page\n";
echo "2. 🔍 Make sure you're on the 'Reactivation Requests' tab, not 'Banned Students'\n";
echo "3. 📱 Try a different browser or incognito mode\n";
echo "4. 🔧 Check if JavaScript is enabled\n";
echo "5. 📊 Verify there are actually pending requests (see above)\n";

echo "\n🎯 FOR TESTING PURPOSES:\n";
echo "═" . str_repeat("═", 25) . "\n";

if ($activeBans > 0 && $pendingRequests === 0) {
    echo "💡 To create a test reactivation request:\n";
    $ban = ExamBan::with(['user', 'subject'])->where('is_active', true)->first();
    if ($ban) {
        echo "1. 🔐 Login as student: {$ban->user->email}\n";
        echo "2. 🌐 Go to: /student/reactivation\n";
        echo "3. 📝 Click 'Request Reactivation' for {$ban->subject->name}\n";
        echo "4. ✍️ Write a request message and submit\n";
        echo "5. 🔄 Then login as admin and check /admin/security\n";
    }
} elseif ($activeBans === 0) {
    echo "💡 To create a test scenario:\n";
    echo "1. 🔐 Login as a student\n";
    echo "2. 📚 Start an exam\n";
    echo "3. 🔄 Switch to another tab (triggers ban)\n";
    echo "4. 📝 Request reactivation from dashboard\n";
    echo "5. 🔄 Login as admin to see the request\n";
}

echo "\n🔗 DIRECT ADMIN LINKS:\n";
echo "═" . str_repeat("═", 25) . "\n";
echo "• Main security page: /admin/security\n";
echo "• All reactivation requests: /admin/security/reactivation-requests\n";

if ($pendingRequests > 0) {
    $firstRequest = ReactivationRequest::where('status', 'pending')->first();
    echo "• First pending request: /admin/security/reactivation-requests/{$firstRequest->id}\n";
}

echo "\n🏁 SUMMARY:\n";
echo "═" . str_repeat("═", 15) . "\n";

if ($pendingRequests > 0) {
    echo "✅ System is working - there ARE reactivation requests waiting\n";
    echo "🎯 Admin should click 'Reactivation Requests' tab to see them\n";
    echo "🔄 If you see 'no mercy', you're looking at the wrong tab or have a browser issue\n";
} else {
    echo "❌ No reactivation requests in system yet\n";
    echo "💡 Follow the testing steps above to create a request\n";
    echo "🔄 Then check the admin dashboard\n";
}

echo "\n🔚 DIAGNOSTIC COMPLETE\n";