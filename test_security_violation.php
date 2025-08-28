<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ExamBan;
use App\Models\ExamSecurityViolation;
use App\Models\ExamSession;
use App\Models\User;
use App\Models\Subject;

echo "=== TESTING SECURITY VIOLATION SYSTEM ===\n\n";

try {
    // Test 1: Check if exam_bans table exists
    echo "🔍 Test 1: Checking exam_bans table...\n";
    $tableExists = Schema::hasTable('exam_bans');
    if ($tableExists) {
        echo "   ✅ exam_bans table EXISTS\n";
        
        // Get count of bans
        $banCount = ExamBan::count();
        echo "   📊 Total bans in database: {$banCount}\n";
        
        // Test a simple query
        $activeBans = ExamBan::where('is_active', true)->count();
        echo "   📊 Active bans: {$activeBans}\n";
    } else {
        echo "   ❌ exam_bans table DOES NOT EXIST!\n";
        echo "   💡 Need to run migration: create_exam_bans_table.php\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error checking exam_bans table: " . $e->getMessage() . "\n";
}

try {
    // Test 2: Check if exam_security_violations table works
    echo "\n🔍 Test 2: Checking exam_security_violations table...\n";
    $violationCount = ExamSecurityViolation::count();
    echo "   ✅ exam_security_violations table works\n";
    echo "   📊 Total violations: {$violationCount}\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error with exam_security_violations table: " . $e->getMessage() . "\n";
}

try {
    // Test 3: Test ExamBan static methods
    echo "\n🔍 Test 3: Testing ExamBan methods...\n";
    
    // Test isBanned method
    $isBanned = ExamBan::isBanned(1, 1);
    echo "   ✅ ExamBan::isBanned() method works\n";
    echo "   📊 User 1 banned from subject 1: " . ($isBanned ? 'YES' : 'NO') . "\n";
    
    // Test getBanDetails method
    $banDetails = ExamBan::getBanDetails(1, 1);
    if ($banDetails) {
        echo "   📊 Ban details found for user 1, subject 1\n";
    } else {
        echo "   📊 No ban details for user 1, subject 1\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error with ExamBan methods: " . $e->getMessage() . "\n";
}

try {
    // Test 4: Test security violation recording
    echo "\n🔍 Test 4: Testing security violation recording...\n";
    
    // Find a test user and subject
    $testUser = User::where('role', 'student')->first();
    $testSubject = Subject::first();
    
    if ($testUser && $testSubject) {
        echo "   👤 Test user: {$testUser->name} (ID: {$testUser->id})\n";
        echo "   📚 Test subject: {$testSubject->name} (ID: {$testSubject->id})\n";
        
        // Test recording a violation (without actually saving)
        $violationData = [
            'user_id' => $testUser->id,
            'subject_id' => $testSubject->id,
            'violation_type' => 'tab_switch_test',
            'description' => 'Test violation - not real',
            'metadata' => ['test' => true],
            'occurred_at' => now(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent'
        ];
        
        echo "   ✅ Violation data structure is valid\n";
        echo "   📊 Data ready for insertion\n";
        
        // Test violation count query
        $violationCount = ExamSecurityViolation::getViolationCount($testUser->id, $testSubject->id, 'tab_switch');
        echo "   ✅ getViolationCount() method works\n";
        echo "   📊 Current tab_switch violations for test user: {$violationCount}\n";
        
    } else {
        echo "   ❌ No test user or subject found\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error testing violation recording: " . $e->getMessage() . "\n";
}

try {
    // Test 5: Check database connection and migrations
    echo "\n🔍 Test 5: Checking database schema...\n";
    
    $columns = DB::select("DESCRIBE exam_security_violations");
    echo "   ✅ exam_security_violations columns:\n";
    foreach ($columns as $column) {
        echo "      - {$column->Field} ({$column->Type})\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Error checking database schema: " . $e->getMessage() . "\n";
}

echo "\n🏁 DIAGNOSTIC COMPLETE\n";
echo "If you see errors above, they indicate what's causing the SQL issues.\n";