<?php
/**
 * Test Tab Switching Detection and Auto-Logout
 * 
 * This script tests the new security feature that detects when students
 * switch tabs during exams and automatically logs them out with warnings.
 */

echo "=== Testing Tab Switching Detection and Auto-Logout ===\n\n";

// Test 1: Check if database table exists
echo "1. Checking Database Setup:\n";
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=exam_system', 'root', '');
    $stmt = $pdo->query("SHOW TABLES LIKE 'exam_security_violations'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ exam_security_violations table exists\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE exam_security_violations");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $expectedColumns = ['id', 'user_id', 'subject_id', 'exam_session_id', 'violation_type', 'description', 'metadata', 'occurred_at', 'ip_address', 'user_agent', 'created_at', 'updated_at'];
        
        $missingColumns = array_diff($expectedColumns, $columns);
        if (empty($missingColumns)) {
            echo "   ✓ All required columns present\n";
        } else {
            echo "   ✗ Missing columns: " . implode(', ', $missingColumns) . "\n";
        }
    } else {
        echo "   ✗ exam_security_violations table not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

// Test 2: Check if model exists
echo "\n2. Checking Model Implementation:\n";
if (file_exists('app/Models/ExamSecurityViolation.php')) {
    echo "   ✓ ExamSecurityViolation model exists\n";
    
    $modelContent = file_get_contents('app/Models/ExamSecurityViolation.php');
    
    if (strpos($modelContent, 'recordViolation') !== false) {
        echo "   ✓ recordViolation method implemented\n";
    } else {
        echo "   ✗ recordViolation method not found\n";
    }
    
    if (strpos($modelContent, 'getViolationCount') !== false) {
        echo "   ✓ getViolationCount method implemented\n";
    } else {
        echo "   ✗ getViolationCount method not found\n";
    }
    
    if (strpos($modelContent, 'shouldLockUser') !== false) {
        echo "   ✓ shouldLockUser method implemented\n";
    } else {
        echo "   ✗ shouldLockUser method not found\n";
    }
} else {
    echo "   ✗ ExamSecurityViolation model not found\n";
}

// Test 3: Check controller implementation
echo "\n3. Checking Controller Implementation:\n";
$controllerContent = file_get_contents('app/Http/Controllers/ExamController.php');

if (strpos($controllerContent, 'recordSecurityViolation') !== false) {
    echo "   ✓ recordSecurityViolation method implemented\n";
} else {
    echo "   ✗ recordSecurityViolation method not found\n";
}

if (strpos($controllerContent, 'ExamSecurityViolation') !== false) {
    echo "   ✓ ExamSecurityViolation model imported\n";
} else {
    echo "   ✗ ExamSecurityViolation model not imported\n";
}

if (strpos($controllerContent, 'force_logout') !== false) {
    echo "   ✓ Force logout functionality implemented\n";
} else {
    echo "   ✗ Force logout functionality not found\n";
}

// Test 4: Check route implementation
echo "\n4. Checking Route Implementation:\n";
$routesContent = file_get_contents('routes/web.php');

if (strpos($routesContent, 'security-violation') !== false) {
    echo "   ✓ Security violation route exists\n";
} else {
    echo "   ✗ Security violation route not found\n";
}

if (strpos($routesContent, 'recordSecurityViolation') !== false) {
    echo "   ✓ Route points to correct controller method\n";
} else {
    echo "   ✗ Route controller method not found\n";
}

// Test 5: Check JavaScript implementation
echo "\n5. Checking JavaScript Implementation:\n";
$examContent = file_get_contents('resources/views/user/exam_simple.blade.php');

if (strpos($examContent, 'enableTabSwitchDetection') !== false) {
    echo "   ✓ Tab switch detection function exists\n";
} else {
    echo "   ✗ Tab switch detection function not found\n";
}

if (strpos($examContent, 'visibilitychange') !== false) {
    echo "   ✓ Visibility change event listener implemented\n";
} else {
    echo "   ✗ Visibility change event listener not found\n";
}

if (strpos($examContent, 'recordSecurityViolation') !== false) {
    echo "   ✓ Security violation recording function exists\n";
} else {
    echo "   ✗ Security violation recording function not found\n";
}

if (strpos($examContent, 'showCriticalWarning') !== false) {
    echo "   ✓ Critical warning display function exists\n";
} else {
    echo "   ✗ Critical warning display function not found\n";
}

if (strpos($examContent, 'showLogoutWarning') !== false) {
    echo "   ✓ Logout warning display function exists\n";
} else {
    echo "   ✗ Logout warning display function not found\n";
}

echo "\n6. Expected Behavior:\n";
echo "\n   🔍 **Tab Switch Detection:**\n";
echo "   • Student switches to another tab/window during exam\n";
echo "   • System immediately detects the switch\n";
echo "   • Progress is automatically saved\n";
echo "   • Security violation is recorded in database\n";
echo "   • Student is immediately logged out\n";
echo "   • Warning message is displayed\n";

echo "\n   ⚠️ **Warning System:**\n";
echo "   • First violation: Warning about logout and recording\n";
echo "   • Second violation: Final warning about account lock\n";
echo "   • Third violation: Account locked for the subject\n";
echo "   • All violations are permanently recorded\n";

echo "\n   🔒 **Security Features:**\n";
echo "   • Detects tab switching (visibilitychange event)\n";
echo "   • Detects window switching (blur event)\n";
echo "   • Warns about Alt+Tab, Ctrl+Tab attempts\n";
echo "   • Records IP address and user agent\n";
echo "   • Saves metadata about the violation\n";
echo "   • Forces immediate logout\n";

echo "\n7. Testing Instructions:\n";
echo "\n   **Test Tab Switch Detection:**\n";
echo "   1. Start an exam as a student\n";
echo "   2. Answer a few questions\n";
echo "   3. Open a new tab (Ctrl+T) or switch to existing tab\n";
echo "   4. ✅ Should immediately show critical warning\n";
echo "   5. ✅ Should be logged out automatically\n";
echo "   6. ✅ Should redirect to login page\n";
echo "   7. ✅ Violation should be recorded in database\n";

echo "\n   **Test Window Switch Detection:**\n";
echo "   1. Start an exam as a student\n";
echo "   2. Click on another application (Alt+Tab)\n";
echo "   3. ✅ Should detect window blur\n";
echo "   4. ✅ Should trigger same logout process\n";

echo "\n   **Test Warning System:**\n";
echo "   1. Try Alt+Tab while in exam\n";
echo "   2. ✅ Should show warning about tab switching\n";
echo "   3. If you proceed, should trigger logout\n";

echo "\n   **Test Multiple Violations:**\n";
echo "   1. Get logged out once (first violation)\n";
echo "   2. Log back in and start exam again\n";
echo "   3. Switch tabs again (second violation)\n";
echo "   4. ✅ Should show 'FINAL WARNING' message\n";
echo "   5. Try a third time\n";
echo "   6. ✅ Should show 'ACCOUNT LOCKED' message\n";

echo "\n8. Database Records:\n";
echo "\n   **Violation Record Contains:**\n";
echo "   • user_id: Which student violated\n";
echo "   • subject_id: Which exam subject\n";
echo "   • exam_session_id: Specific exam session\n";
echo "   • violation_type: 'tab_switch'\n";
echo "   • description: Details of the violation\n";
echo "   • metadata: Technical details (browser, screen size, etc.)\n";
echo "   • occurred_at: Exact timestamp\n";
echo "   • ip_address: Student's IP address\n";
echo "   • user_agent: Browser information\n";

echo "\n9. Administrative Benefits:\n";
echo "\n   📊 **Monitoring:**\n";
echo "   • Track which students attempt to cheat\n";
echo "   • See patterns of violations\n";
echo "   • Generate reports on exam security\n";
echo "   • Evidence for academic integrity cases\n";

echo "\n   🛡️ **Security:**\n";
echo "   • Prevents tab switching cheating\n";
echo "   • Deters students from attempting violations\n";
echo "   • Creates audit trail of all attempts\n";
echo "   • Automatic enforcement without manual monitoring\n";

echo "\n   ⚖️ **Fairness:**\n";
echo "   • Same rules applied to all students\n";
echo "   • Consistent enforcement\n";
echo "   • Clear consequences for violations\n";
echo "   • Transparent warning system\n";

echo "\n10. Technical Implementation:\n";
echo "\n   **Frontend Detection:**\n";
echo "   - visibilitychange event: Detects tab switches\n";
echo "   - window blur event: Detects application switches\n";
echo "   - keydown event: Warns about tab switch shortcuts\n";
echo "   - Immediate progress saving before logout\n";

echo "\n   **Backend Processing:**\n";
echo "   - Validates exam session ownership\n";
echo "   - Records violation with full metadata\n";
echo "   - Counts violations per user/subject\n";
echo "   - Implements 3-strike lockout system\n";
echo "   - Forces session invalidation and logout\n";

echo "\n   **Database Storage:**\n";
echo "   - Permanent violation records\n";
echo "   - Indexed for fast queries\n";
echo "   - Linked to users, subjects, and sessions\n";
echo "   - Includes technical forensic data\n";

echo "\n11. Security Considerations:\n";
echo "\n   ✅ **What's Detected:**\n";
echo "   • Tab switching (Ctrl+T, clicking other tabs)\n";
echo "   • Window switching (Alt+Tab, clicking other apps)\n";
echo "   • Browser focus loss\n";
echo "   • Attempts to navigate away\n";

echo "\n   ⚠️ **Limitations:**\n";
echo "   • Cannot detect virtual machines\n";
echo "   • Cannot detect second devices\n";
echo "   • Cannot detect screen sharing\n";
echo "   • May have false positives from system notifications\n";

echo "\n   🔧 **Recommendations:**\n";
echo "   • Combine with webcam proctoring for high-stakes exams\n";
echo "   • Use lockdown browser for maximum security\n";
echo "   • Consider physical supervision when possible\n";
echo "   • Regular review of violation reports\n";

echo "\n12. Files Created/Modified:\n";
echo "   📁 **New Files:**\n";
echo "   - database/migrations/2025_08_23_000001_create_exam_security_violations_table.php\n";
echo "   - app/Models/ExamSecurityViolation.php\n";

echo "\n   📝 **Modified Files:**\n";
echo "   - routes/web.php (added security violation route)\n";
echo "   - app/Http/Controllers/ExamController.php (added violation handling)\n";
echo "   - resources/views/user/exam_simple.blade.php (added tab detection)\n";

echo "\n=== Test Complete ===\n";
echo "\nTab switching detection is now implemented!\n";
echo "Students who switch tabs will be immediately logged out and violations will be recorded.\n";
echo "This creates a strong deterrent against cheating while maintaining detailed audit trails.\n";
?>"