<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Subject;
use App\Models\ExamSession;
use App\Models\ExamSecurityViolation;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n🔍 RED WARNING SCREEN DIAGNOSTIC TEST\n";
echo "=====================================\n\n";

try {
    // Test 1: Check database tables
    echo "1. Checking Database Setup...\n";
    
    $tables = [
        'exam_security_violations',
        'exam_sessions',
        'users',
        'subjects'
    ];
    
    foreach ($tables as $table) {
        if (DB::getSchemaBuilder()->hasTable($table)) {
            echo "   ✅ Table '{$table}' exists\n";
            
            // Check table structure for exam_security_violations
            if ($table === 'exam_security_violations') {
                $columns = DB::getSchemaBuilder()->getColumnListing($table);
                $requiredColumns = ['id', 'user_id', 'subject_id', 'exam_session_id', 'violation_type', 'description', 'metadata', 'occurred_at', 'ip_address', 'user_agent', 'created_at', 'updated_at'];
                
                $missingColumns = array_diff($requiredColumns, $columns);
                if (empty($missingColumns)) {
                    echo "      ✅ All required columns present\n";
                } else {
                    echo "      ❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
                    return;
                }
            }
        } else {
            echo "   ❌ Table '{$table}' NOT FOUND!\n";
            return;
        }
    }
    
    // Test 2: Get test data
    echo "\n2. Getting Test Data...\n";
    
    $user = User::where('role', 'user')->first();
    if (!$user) {
        echo "   ❌ No student users found!\n";
        return;
    }
    echo "   ✅ Test user: {$user->name} (ID: {$user->id})\n";
    
    $subject = Subject::first();
    if (!$subject) {
        echo "   ❌ No subjects found!\n";
        return;
    }
    echo "   ✅ Test subject: {$subject->name} (ID: {$subject->id})\n";
    
    // Test 3: Create exam session
    echo "\n3. Testing Exam Session Creation...\n";
    
    // Clean up any existing active sessions first
    $existingSessions = ExamSession::where('user_id', $user->id)
        ->where('subject_id', $subject->id)
        ->where('is_active', true)
        ->get();
    
    if ($existingSessions->count() > 0) {
        echo "   🧹 Cleaning up {$existingSessions->count()} existing active sessions...\n";
        foreach ($existingSessions as $session) {
            $session->update(['is_active' => false, 'completed_at' => now()]);
        }
    }
    
    try {
        $examSession = ExamSession::create([
            'user_id' => $user->id,
            'subject_id' => $subject->id,
            'started_at' => now(),
            'expires_at' => now()->addMinutes(60),
            'duration_minutes' => 60,
            'answers' => json_encode([]),
            'current_question_index' => 0,
            'is_active' => true,
            'last_activity_at' => now()
        ]);
        echo "   ✅ Exam session created successfully (ID: {$examSession->id})\n";
    } catch (Exception $e) {
        echo "   ❌ Failed to create exam session: " . $e->getMessage() . "\n";
        return;
    }
    
    // Test 4: Test security violation recording
    echo "\n4. Testing Security Violation Recording...\n";
    
    try {
        $violation = ExamSecurityViolation::recordViolation(
            $user->id,
            $subject->id,
            'tab_switch_attempt',
            'TEST: Student attempted tab switching during exam',
            $examSession->id,
            [
                'test_mode' => true,
                'timestamp' => now()->toISOString(),
                'attempt_count' => 1,
                'user_agent' => 'Test Browser'
            ]
        );
        echo "   ✅ Security violation recorded successfully (ID: {$violation->id})\n";
    } catch (Exception $e) {
        echo "   ❌ FAILED to record security violation: " . $e->getMessage() . "\n";
        echo "   📝 This is likely the SQL error preventing the warning screen!\n";
        echo "   🔍 Error details: " . $e->getFile() . " line " . $e->getLine() . "\n";
        return;
    }
    
    // Test 5: Check JavaScript implementation
    echo "\n5. Checking JavaScript Implementation...\n";
    
    $examFile = 'resources/views/user/exam_simple.blade.php';
    if (file_exists($examFile)) {
        $examContent = file_get_contents($examFile);
        
        // Check for required functions
        $functions = [
            'showCriticalWarning' => strpos($examContent, 'function showCriticalWarning') !== false,
            'showContinueWarning' => strpos($examContent, 'function showContinueWarning') !== false,
            'showPermanentBlockWarning' => strpos($examContent, 'function showPermanentBlockWarning') !== false,
            'recordSecurityViolation' => strpos($examContent, 'function recordSecurityViolation') !== false,
            'enableTabSwitchDetection' => strpos($examContent, 'function enableTabSwitchDetection') !== false
        ];
        
        foreach ($functions as $func => $exists) {
            if ($exists) {
                echo "   ✅ Function '{$func}' exists\n";
            } else {
                echo "   ❌ Function '{$func}' MISSING!\n";
            }
        }
        
        // Check for event listeners
        $listeners = [
            'visibilitychange' => strpos($examContent, 'visibilitychange') !== false,
            'keydown blocking' => strpos($examContent, 'e.ctrlKey && e.key === \'t\'') !== false,
            'contextmenu blocking' => strpos($examContent, 'contextmenu') !== false
        ];
        
        foreach ($listeners as $listener => $exists) {
            if ($exists) {
                echo "   ✅ Event listener '{$listener}' implemented\n";
            } else {
                echo "   ❌ Event listener '{$listener}' MISSING!\n";
            }
        }
        
    } else {
        echo "   ❌ Exam file not found: {$examFile}\n";
    }
    
    // Test 6: Check route configuration
    echo "\n6. Checking Route Configuration...\n";
    
    $routeFile = 'routes/web.php';
    if (file_exists($routeFile)) {
        $routeContent = file_get_contents($routeFile);
        
        if (strpos($routeContent, 'security-violation') !== false) {
            echo "   ✅ Security violation route exists\n";
        } else {
            echo "   ❌ Security violation route MISSING!\n";
        }
        
        if (strpos($routeContent, 'recordSecurityViolation') !== false) {
            echo "   ✅ Route points to correct controller method\n";
        } else {
            echo "   ❌ Route controller method not configured!\n";
        }
    }
    
    // Test 7: Simulate frontend behavior
    echo "\n7. Testing System Behavior...\n";
    
    // Test what happens with multiple violations
    $violationCount = ExamSecurityViolation::getViolationCount($user->id, $subject->id, 'tab_switch_attempt');
    echo "   📊 Current violation count: {$violationCount}\n";
    
    if ($violationCount >= 3) {
        echo "   🚫 User should be PERMANENTLY BLOCKED\n";
    } elseif ($violationCount >= 2) {
        echo "   🚨 User should see FINAL WARNING with continue button\n";
    } elseif ($violationCount >= 1) {
        echo "   ⚠️  User should see WARNING with continue button\n";
    } else {
        echo "   ✅ User is clean, no violations\n";
    }
    
    // Test 8: Check what students should see
    echo "\n8. Expected Student Experience...\n";
    
    echo "   🎯 When students try Ctrl+T or Alt+Tab:\n";
    echo "      1. Action should be BLOCKED immediately\n";
    echo "      2. Red warning screen should appear covering entire page\n";
    echo "      3. Warning should show violation count (1/3, 2/3, etc.)\n";
    echo "      4. First 2 violations: Continue button appears\n";
    echo "      5. 3rd violation: Permanent block, no continue button\n";
    echo "      6. All violations recorded in database\n";
    
    echo "\n   🔧 Troubleshooting Steps:\n";
    echo "      1. Open browser console during exam\n";
    echo "      2. Try pressing Ctrl+T\n";
    echo "      3. Check console for JavaScript errors\n";
    echo "      4. Verify network requests to /student/exam/security-violation\n";
    echo "      5. Check if showCriticalWarning function is called\n";
    
    // Clean up test data
    echo "\n9. Cleaning Up Test Data...\n";
    try {
        $violation->delete();
        $examSession->delete();
        echo "   ✅ Test data cleaned up\n";
    } catch (Exception $e) {
        echo "   ⚠️  Warning: Could not clean up test data: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ DIAGNOSTIC COMPLETE!\n";
    echo "\n📋 SUMMARY:\n";
    echo "   - Database tables: ✅ Working\n";
    echo "   - Security violation recording: ✅ Working\n";
    echo "   - JavaScript functions: Check results above\n";
    echo "   - Routes: Check results above\n";
    echo "\n🔍 If students still don't see red warning screen:\n";
    echo "   1. Check browser console for JavaScript errors\n";
    echo "   2. Verify CSRF token is present in page\n";
    echo "   3. Check network tab for failed requests\n";
    echo "   4. Ensure showCriticalWarning function is defined\n";
    echo "   5. Test with different browsers\n\n";
    
} catch (Exception $e) {
    echo "\n❌ DIAGNOSTIC FAILED: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    echo "   Stack trace: " . $e->getTraceAsString() . "\n\n";
}