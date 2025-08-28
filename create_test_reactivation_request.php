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
use Illuminate\Support\Facades\DB;

echo "🎯 CREATING TEST REACTIVATION REQUEST FOR ADMIN DEMO\n";
echo "=" . str_repeat("=", 60) . "\n\n";

try {
    DB::beginTransaction();
    
    // Get test data
    $student = User::where('role', 'student')->first();
    $subject = Subject::first();
    
    if (!$student || !$subject) {
        echo "❌ Need at least one student and one subject in database\n";
        exit(1);
    }
    
    echo "📋 Setting up test scenario...\n";
    echo "👤 Student: {$student->name} ({$student->email})\n";
    echo "📚 Subject: {$subject->name}\n\n";
    
    // Clean up any existing data for this student/subject
    echo "🧹 Cleaning up existing data...\n";
    ReactivationRequest::where('user_id', $student->id)->where('subject_id', $subject->id)->delete();
    ExamBan::where('user_id', $student->id)->where('subject_id', $subject->id)->delete();
    ExamSecurityViolation::where('user_id', $student->id)->where('subject_id', $subject->id)->delete();
    
    // Step 1: Create a tab switching violation
    echo "⚡ Creating tab switching violation...\n";
    $violation = ExamSecurityViolation::create([
        'user_id' => $student->id,
        'subject_id' => $subject->id,
        'exam_session_id' => null,
        'violation_type' => 'tab_switch',
        'description' => 'DEMO: Student switched tabs during exam - immediate ban triggered',
        'metadata' => [
            'detection_method' => 'blur_focus_loss',
            'policy' => 'IMMEDIATE_BAN_ON_FIRST_VIOLATION',
            'demo_data' => true
        ],
        'occurred_at' => now()->subMinutes(30),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Mozilla/5.0 (Demo Browser)'
    ]);
    echo "   ✅ Violation created (ID: {$violation->id})\n";
    
    // Step 2: Create immediate ban
    echo "🚫 Creating immediate ban...\n";
    $ban = ExamBan::create([
        'user_id' => $student->id,
        'subject_id' => $subject->id,
        'ban_reason' => 'IMMEDIATE_TAB_SWITCH_BAN - Demo ban for testing admin reactivation interface',
        'violation_details' => [
            [
                'type' => 'tab_switch',
                'description' => 'Demo violation for admin testing',
                'occurred_at' => now()->subMinutes(30)->toISOString(),
                'policy' => 'IMMEDIATE_BAN_ON_FIRST_VIOLATION',
                'demo_data' => true
            ]
        ],
        'total_violations' => 1,
        'banned_at' => now()->subMinutes(30),
        'is_active' => true,
        'is_permanent' => true
    ]);
    echo "   ✅ Ban created (ID: {$ban->id})\n";
    
    // Step 3: Create reactivation request
    echo "📝 Creating student reactivation request...\n";
    $requestMessage = "Dear Administrator,\n\nI sincerely apologize for the tab switching violation that occurred during my {$subject->name} exam. I accidentally clicked on another browser tab while trying to check the time, which I now understand is strictly prohibited during exams.\n\nI fully acknowledge that this was my mistake and I take complete responsibility for violating the exam integrity rules. I understand the importance of maintaining a fair and secure exam environment for all students.\n\nI am requesting reactivation so that I can retake the exam properly. I promise to:\n- Keep only the exam tab open during the test\n- Not switch to any other applications or tabs\n- Follow all exam security protocols strictly\n- Focus entirely on the exam without any distractions\n\nThis was my first violation and I have learned from this experience. I would be very grateful for a second chance to demonstrate my knowledge in {$subject->name} while following all the rules properly.\n\nThank you for your time and consideration.\n\nRespectfully,\n{$student->name}";
    
    $requestResult = ReactivationRequest::createRequest(
        $student->id,
        $subject->id,
        $ban->id,
        $requestMessage
    );
    
    if ($requestResult['success']) {
        $request = $requestResult['request'];
        echo "   ✅ Reactivation request created (ID: {$request->id})\n";
        echo "   📅 Requested at: {$request->requested_at}\n";
        echo "   📝 Status: {$request->status}\n";
    } else {
        echo "   ❌ Failed to create request: {$requestResult['message']}\n";
        throw new Exception("Could not create reactivation request");
    }
    
    DB::commit();
    
    echo "\n🎉 TEST DATA CREATED SUCCESSFULLY!\n\n";
    
    echo "🎯 NOW FOR ADMIN TO SEE THE REACTIVATION REQUEST:\n";
    echo "═" . str_repeat("═", 55) . "\n";
    echo "1. 🔐 Login as admin\n";
    echo "2. 🌐 Go to: http://your-domain/admin/security\n";
    echo "3. 📋 Click the 'Reactivation Requests' tab (should show badge with '1')\n";
    echo "4. 👀 You should see:\n";
    echo "   • Student Name: {$student->name}\n";
    echo "   • Student Email: {$student->email}\n";
    if ($student->registration_number) {
        echo "   • Registration: {$student->registration_number}\n";
    }
    echo "   • Subject: {$subject->name}\n";
    echo "   • Status: Pending Review (yellow badge)\n";
    echo "   • Action buttons: 👁️ VIEW, ✅ APPROVE, ❌ REJECT\n\n";
    
    echo "✅ TO APPROVE THE REQUEST:\n";
    echo "─" . str_repeat("─", 30) . "\n";
    echo "• Click the green ✅ APPROVE button\n";
    echo "• Confirm the approval\n";
    echo "• Student will be immediately unbanned\n";
    echo "• Student can retake the {$subject->name} exam\n\n";
    
    echo "❌ TO REJECT THE REQUEST:\n";
    echo "─" . str_repeat("─", 30) . "\n";
    echo "• Click the red ❌ REJECT button\n";
    echo "• Enter a rejection reason\n";
    echo "• Student remains banned but can submit a new request\n\n";
    
    echo "🔗 DIRECT LINKS FOR ADMIN:\n";
    echo "─" . str_repeat("─", 30) . "\n";
    echo "• Security Dashboard: /admin/security\n";
    echo "• All Reactivation Requests: /admin/security/reactivation-requests\n";
    echo "• This Specific Request: /admin/security/reactivation-requests/{$request->id}\n\n";
    
    echo "⚠️  IF YOU STILL SEE 'NO MERCY':\n";
    echo "─" . str_repeat("─", 35) . "\n";
    echo "• Make sure you clicked 'Reactivation Requests' tab, not 'Banned Students'\n";
    echo "• Clear browser cache and refresh\n";
    echo "• Try incognito/private browsing mode\n";
    echo "• Check browser console for JavaScript errors\n";
    echo "• Verify you're logged in as admin, not student\n\n";
    
    echo "🧪 TO CLEAN UP TEST DATA LATER:\n";
    echo "─" . str_repeat("─", 35) . "\n";
    echo "Run this in the admin dashboard or use these SQL commands:\n";
    echo "DELETE FROM reactivation_requests WHERE id = {$request->id};\n";
    echo "DELETE FROM exam_bans WHERE id = {$ban->id};\n";
    echo "DELETE FROM exam_security_violations WHERE id = {$violation->id};\n";
    
    echo "\n🏁 SETUP COMPLETE - TEST THE ADMIN INTERFACE NOW!\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}