<?php

echo "=== TESTING SUBJECTS ENDPOINT ISSUE ===\n\n";

// Test 1: Check Route Registration
echo "🔍 TEST 1: ROUTE REGISTRATION\n";
echo "===============================\n";

$routeOutput = shell_exec('php artisan route:list --name=exam.reset.subjects 2>&1');
if (strpos($routeOutput, 'admin.exam.reset.subjects') !== false) {
    echo "✅ Route registered correctly\n";
    echo "Route details:\n" . $routeOutput . "\n";
} else {
    echo "❌ Route not found\n";
    echo "Available routes:\n";
    echo shell_exec('php artisan route:list --name=exam.reset 2>&1');
}

// Test 2: Check Controller Method
echo "\n🔍 TEST 2: CONTROLLER METHOD\n";
echo "============================\n";

if (file_exists('app/Http/Controllers/Admin/ExamResetController.php')) {
    $controller = file_get_contents('app/Http/Controllers/Admin/ExamResetController.php');
    
    if (strpos($controller, 'getSubjectsForClass') !== false) {
        echo "✅ Controller method exists: getSubjectsForClass\n";
    } else {
        echo "❌ Controller method missing: getSubjectsForClass\n";
    }
    
    if (strpos($controller, 'where(\'class_id\'') !== false) {
        echo "✅ Controller filters subjects by class_id\n";
    } else {
        echo "❌ Controller doesn't filter by class_id\n";
    }
} else {
    echo "❌ Controller file not found\n";
}

// Test 3: Check Database Structure
echo "\n🔍 TEST 3: DATABASE STRUCTURE\n";
echo "=============================\n";

try {
    // Bootstrap Laravel
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Check if we can connect to database
    echo "✅ Database connection: Working\n";
    
    $classCount = \App\Models\ClassModel::count();
    echo "✅ Classes in database: {$classCount}\n";
    
    $subjectCount = \App\Models\Subject::count();
    echo "✅ Subjects in database: {$subjectCount}\n";
    
    // Check if subjects have class_id
    $subjectsWithClass = \App\Models\Subject::whereNotNull('class_id')->count();
    echo "✅ Subjects with class_id: {$subjectsWithClass}\n";
    
    // Show sample classes
    echo "\nSample Classes:\n";
    $sampleClasses = \App\Models\ClassModel::take(3)->get(['id', 'name']);
    foreach ($sampleClasses as $class) {
        $subjectCount = \App\Models\Subject::where('class_id', $class->id)->count();
        echo "  - Class {$class->id}: {$class->name} - {$subjectCount} subjects\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 4: Manual Endpoint Test
echo "\n🔍 TEST 4: MANUAL ENDPOINT TEST\n";
echo "===============================\n";

// Try to make a request to the endpoint manually
$testClassId = 1; // Test with class ID 1
$url = "http://web-portal.test/admin/exam-reset/subjects/{$testClassId}";

echo "Testing URL: {$url}\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: application/json',
            'User-Agent: Test Script'
        ],
        'timeout' => 10
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response !== false) {
    echo "✅ Endpoint accessible\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
    
    $data = json_decode($response, true);
    if (is_array($data)) {
        echo "✅ Valid JSON response\n";
        echo "Subjects returned: " . count($data) . "\n";
    } else {
        echo "❌ Invalid JSON response\n";
    }
} else {
    $error = error_get_last();
    echo "❌ Endpoint not accessible\n";
    echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
}

// Test 5: Check JavaScript in View
echo "\n🔍 TEST 5: JAVASCRIPT CHECK\n";
echo "===========================\n";

if (file_exists('resources/views/admin/exam-reset/index.blade.php')) {
    $view = file_get_contents('resources/views/admin/exam-reset/index.blade.php');
    
    $jsChecks = [
        '$.get(' => 'AJAX GET request',
        'subjects/${classId}' => 'Correct URL pattern',
        '.change(function()' => 'Change event listener',
        'subjectSelect.append' => 'Subject appending'
    ];
    
    foreach ($jsChecks as $pattern => $description) {
        if (strpos($view, $pattern) !== false) {
            echo "✅ JavaScript check: {$description}\n";
        } else {
            echo "❌ JavaScript missing: {$description}\n";
        }
    }
} else {
    echo "❌ Exam reset view not found\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
echo "Check the results above to identify the issue.\n";

?>