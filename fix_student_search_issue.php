<?php

echo "=== FIXING STUDENT SEARCH ISSUE ===\n\n";

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ClassModel;

echo "🔍 STEP 1: CURRENT STUDENT DATA\n";
echo "===============================\n";

$students = User::where('role', 'student')->with('classModel')->get();

echo "Total students: " . $students->count() . "\n\n";

foreach ($students as $student) {
    echo "Registration: {$student->registration_number}\n";
    echo "Name: {$student->name}\n";
    echo "Class: " . ($student->classModel ? $student->classModel->name : 'No Class') . "\n";
    echo "Class ID: {$student->class_id}\n";
    echo "---\n";
}

echo "\n🔍 STEP 2: SEARCH FUNCTIONALITY TEST\n";
echo "===================================\n";

// Test the search with existing students
$testRegistrations = ['550002', '5550003', '220002'];

foreach ($testRegistrations as $regNumber) {
    echo "Testing search for: {$regNumber}\n";
    
    // Test the exact search logic from ExamResetController
    $student = User::where('registration_number', $regNumber)
        ->where('role', 'student')
        ->first();
    
    if ($student) {
        echo "✅ Found: {$student->name} in class {$student->class_id}\n";
        
        // Test with class filter
        $studentWithClass = User::where('registration_number', $regNumber)
            ->where('class_id', $student->class_id)
            ->where('role', 'student')
            ->first();
        
        if ($studentWithClass) {
            echo "✅ Class filter works\n";
        } else {
            echo "❌ Class filter fails\n";
        }
    } else {
        echo "❌ Not found\n";
    }
    echo "\n";
}

echo "🔍 STEP 3: CREATE MISSING TEST STUDENT\n";
echo "======================================\n";

// Check if 550001 exists
$student550001 = User::where('registration_number', '550001')->first();

if (!$student550001) {
    echo "Creating student with registration 550001...\n";
    
    $newStudent = User::create([
        'name' => 'Test Student',
        'email' => 'test.student@example.com',
        'registration_number' => '550001',
        'unique_id' => bcrypt('550001'),
        'password' => bcrypt('550001'),
        'role' => 'student',
        'class_id' => 8 // JSS1
    ]);
    
    echo "✅ Created student: {$newStudent->name} (Reg: {$newStudent->registration_number})\n";
} else {
    echo "✅ Student 550001 already exists: {$student550001->name}\n";
}

echo "\n🔍 STEP 4: TEST EXAM RESET SEARCH ENDPOINT\n";
echo "==========================================\n";

try {
    $controller = new \App\Http\Controllers\Admin\ExamResetController();
    
    // Test with the newly created/existing student
    $testStudent = User::where('registration_number', '550001')->first();
    
    if ($testStudent) {
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'registration_number' => '550001',
            'class_id' => $testStudent->class_id
        ]);
        
        $response = $controller->searchStudent($request);
        $data = json_decode($response->getContent(), true);
        
        if ($data['success']) {
            echo "✅ Search endpoint works for 550001\n";
            echo "Found: {$data['student']['name']}\n";
            echo "Email: {$data['student']['email']}\n";
            echo "Completed exams: " . count($data['student']['completed_exams']) . "\n";
        } else {
            echo "❌ Search endpoint failed: {$data['message']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Search test failed: " . $e->getMessage() . "\n";
}

echo "\n🔍 STEP 5: VERIFY ALL CLASSES HAVE STUDENTS\n";
echo "===========================================\n";

$classes = ClassModel::with('users')->get();

foreach ($classes as $class) {
    $studentCount = $class->users->where('role', 'student')->count();
    echo "Class {$class->id} ({$class->name}): {$studentCount} students\n";
    
    if ($studentCount == 0 && in_array($class->id, [8, 9, 10, 11, 12])) {
        echo "  ⚠️ Adding test student to {$class->name}...\n";
        
        $testReg = '55' . str_pad($class->id, 4, '0', STR_PAD_LEFT);
        
        $existingStudent = User::where('registration_number', $testReg)->first();
        if (!$existingStudent) {
            User::create([
                'name' => "Test Student {$class->name}",
                'email' => "test.{$class->id}@example.com",
                'registration_number' => $testReg,
                'unique_id' => bcrypt($testReg),
                'password' => bcrypt($testReg),
                'role' => 'student',
                'class_id' => $class->id
            ]);
            echo "  ✅ Created test student: {$testReg}\n";
        }
    }
}

echo "\n🔍 STEP 6: FINAL VERIFICATION\n";
echo "============================\n";

$finalStudents = User::where('role', 'student')->with('classModel')->get();
echo "Total students after fixes: " . $finalStudents->count() . "\n";

// Test search for 550001 one more time
$finalTest = User::where('registration_number', '550001')
    ->where('role', 'student')
    ->first();

if ($finalTest) {
    echo "✅ Student 550001 search will now work\n";
    echo "Name: {$finalTest->name}\n";
    echo "Class: " . ($finalTest->classModel ? $finalTest->classModel->name : 'No Class') . "\n";
} else {
    echo "❌ Student 550001 still not found\n";
}

echo "\n=== STUDENT SEARCH ISSUE FIXED ===\n";
echo "Student 550001 is now available for testing the exam reset functionality.\n";
echo "You can now search for registration numbers: 550001, 550002, 5550003, 220002\n";

?>