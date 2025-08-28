<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamSecurityViolation;
use App\Models\User;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

echo "🧪 TESTING NEW VIOLATION TRACKING SYSTEM\n";
echo "========================================\n\n";

// Test the new query logic
echo "1. 📊 TESTING VIOLATION QUERY:\n";
echo "==============================\n";

$problematicStudentsQuery = DB::table('exam_security_violations as v')
    ->join('users as u', 'v.user_id', '=', 'u.id')
    ->join('subjects as s', 'v.subject_id', '=', 's.id')
    ->select(
        'u.id as user_id',
        'u.name as user_name',
        'u.email as user_email', 
        'u.registration_number',
        's.id as subject_id',
        's.name as subject_name',
        'v.description',
        'v.occurred_at',
        'v.violation_type',
        DB::raw('COUNT(*) as violation_count'),
        DB::raw('MAX(v.occurred_at) as latest_violation')
    )
    ->where(function($query) {
        $query->where('v.description', 'like', '%NO MERCY%')
              ->orWhere('v.description', 'like', '%banned%')
              ->orWhere('v.description', 'like', '%violation%')
              ->orWhere('v.violation_type', 'tab_switch')
              ->orWhere('v.violation_type', 'security_breach');
    })
    ->groupBy('u.id', 'u.name', 'u.email', 'u.registration_number', 's.id', 's.name', 'v.description', 'v.occurred_at', 'v.violation_type')
    ->orderBy('latest_violation', 'desc');

$results = $problematicStudentsQuery->get();

echo "Found " . $results->count() . " problematic violation records\n\n";

foreach ($results as $index => $result) {
    echo ($index + 1) . ". VIOLATION RECORD:\n";
    echo "   👤 Student: {$result->user_name}\n";
    echo "   📧 Email: {$result->user_email}\n";
    echo "   🎯 Registration: " . ($result->registration_number ?? 'NOT SET') . "\n";
    echo "   📚 Subject: {$result->subject_name}\n";
    echo "   ⚠️  Violation Type: {$result->violation_type}\n";
    echo "   📝 Description: " . substr($result->description, 0, 80) . "...\n";
    echo "   📊 Count: {$result->violation_count}\n";
    echo "   📅 Latest: {$result->latest_violation}\n";
    echo "   ---\n";
}

// Test if we can identify students who should be on banned list
echo "\n2. 🎯 STUDENTS WHO SHOULD APPEAR IN BANNED LIST:\n";
echo "===============================================\n";

$uniqueProblematicStudents = DB::table('exam_security_violations as v')
    ->join('users as u', 'v.user_id', '=', 'u.id')
    ->select(
        'u.id',
        'u.name',
        'u.email',
        'u.registration_number',
        DB::raw('COUNT(DISTINCT v.subject_id) as subjects_with_violations'),
        DB::raw('MAX(v.occurred_at) as latest_violation')
    )
    ->where(function($query) {
        $query->where('v.description', 'like', '%NO MERCY%')
              ->orWhere('v.description', 'like', '%banned%')
              ->orWhere('v.violation_type', 'tab_switch');
    })
    ->groupBy('u.id', 'u.name', 'u.email', 'u.registration_number')
    ->orderBy('latest_violation', 'desc')
    ->get();

echo "Unique students with violations: " . $uniqueProblematicStudents->count() . "\n\n";

foreach ($uniqueProblematicStudents as $index => $student) {
    echo ($index + 1) . ". STUDENT WITH ISSUES:\n";
    echo "   👤 Name: {$student->name}\n";
    echo "   📧 Email: {$student->email}\n";
    echo "   🎯 Registration: " . ($student->registration_number ?? 'NOT SET') . "\n";
    echo "   📚 Subjects with violations: {$student->subjects_with_violations}\n";
    echo "   📅 Latest violation: {$student->latest_violation}\n";
    echo "   ✅ Should appear in banned list: YES\n";
    echo "   ---\n";
}

// Show what the admin dashboard should display
echo "\n3. 🌐 WHAT ADMIN DASHBOARD SHOULD SHOW:\n";
echo "======================================\n";

if ($uniqueProblematicStudents->count() > 0) {
    echo "✅ SUCCESS: Admin dashboard should show these students\n";
    echo "🔧 Tracking method: Registration number and email\n";
    echo "📝 Data source: exam_security_violations table\n";
    echo "🌐 URL: http://web-portal.test/admin/security/banned-students\n\n";
    
    echo "Expected display format:\n";
    foreach ($uniqueProblematicStudents as $student) {
        echo "- {$student->name} (Reg: " . ($student->registration_number ?? 'N/A') . ") - {$student->subjects_with_violations} subject(s)\n";
    }
} else {
    echo "❌ No students found with violations\n";
    echo "💡 This means either:\n";
    echo "   1. No violations have been logged yet\n";
    echo "   2. Violation descriptions don't match our search criteria\n";
    echo "   3. Database structure is different than expected\n";
}

echo "\n✨ Test completed!\n";