<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Http\Controllers\Admin\SecurityViolationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

echo "🔍 DEBUGGING VIOLATION COUNTS IN ADMIN INTERFACE\n";
echo "===============================================\n\n";

try {
    // Create a test request
    $request = new Request();
    
    // Create controller instance
    $controller = new SecurityViolationController();
    
    echo "📊 Calling bannedStudents() method...\n";
    
    // Call the bannedStudents method directly
    $response = $controller->bannedStudents($request);
    
    // Get the view data
    if (method_exists($response, 'getData')) {
        $data = $response->getData();
        $bannedStudents = $data['bannedStudents'];
        
        echo "\n📋 DETAILED VIOLATION COUNTS:\n";
        echo "============================\n\n";
        
        foreach ($bannedStudents as $index => $ban) {
            echo "Student #{$index + 1}: {$ban->user->name}\n";
            echo "  - Subject: {$ban->subject->name}\n";
            echo "  - Ban ID: {$ban->id}\n";
            echo "  - Violation Type: {$ban->violation_type}\n";
            echo "  - Specific Violation Type: {$ban->specific_violation_type}\n";
            echo "  - Total Violations: {$ban->total_violations}\n";
            echo "  - Ban Reason: " . substr($ban->ban_reason, 0, 100) . "...\n";
            
            // Let's manually verify the count
            $userId = $ban->user->id;
            $subjectId = $ban->subject->id;
            
            // Get actual counts from database
            $rightClickCount = DB::table('exam_security_violations')
                ->where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->where('violation_type', 'right_click')
                ->count();
                
            $tabSwitchCount = DB::table('exam_security_violations')
                ->where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->where('violation_type', 'tab_switch')
                ->count();
                
            $totalAllViolations = DB::table('exam_security_violations')
                ->where('user_id', $userId)
                ->where('subject_id', $subjectId)
                ->whereNotIn('violation_type', ['admin_reactivation', 'admin_bulk_reactivation'])
                ->count();
            
            echo "  - ACTUAL DB COUNTS:\n";
            echo "    * Right-clicks: {$rightClickCount}\n";
            echo "    * Tab switches: {$tabSwitchCount}\n";
            echo "    * Total all violations: {$totalAllViolations}\n";
            
            // Determine what the count SHOULD be based on ban type
            $expectedCount = 0;
            if ($ban->violation_type === 'RIGHT_CLICK_BAN') {
                $expectedCount = $rightClickCount;
                echo "    * Expected count (right-click based): {$expectedCount}\n";
            } elseif ($ban->violation_type === 'VIOLATION_BASED_BAN') {
                $expectedCount = $tabSwitchCount;
                echo "    * Expected count (tab switch based): {$expectedCount}\n";
            } elseif ($ban->violation_type === 'ACTIVE_FORMAL_BAN') {
                $expectedCount = $totalAllViolations;
                echo "    * Expected count (formal ban - all violations): {$expectedCount}\n";
            }
            
            if ($ban->total_violations != $expectedCount) {
                echo "  🚨 MISMATCH! Displayed: {$ban->total_violations}, Expected: {$expectedCount}\n";
            } else {
                echo "  ✅ COUNT CORRECT!\n";
            }
            
            echo "\n" . str_repeat("-", 60) . "\n\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🎯 SUMMARY:\n";
echo "This debug script shows the violation counts displayed in the admin interface\n";
echo "and compares them with the actual database counts to identify discrepancies.\n";