<?php

echo "=== COMPLETE ADMIN DASHBOARD REVIEW ===\n\n";

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\UserScore;
use App\Models\Question;

echo "🔍 STEP 1: STUDENT SEARCH ISSUE DIAGNOSIS\n";
echo "=========================================\n";

// Check for student with registration number 550001
$testRegNumbers = ['550001', '550002', '550003'];

foreach ($testRegNumbers as $regNumber) {
    $student = User::where('registration_number', $regNumber)->first();
    
    if ($student) {
        echo "✅ Found student: {$regNumber}\n";
        echo "   - Name: {$student->name}\n";
        echo "   - Email: {$student->email}\n";
        echo "   - Class ID: {$student->class_id}\n";
        echo "   - Role: {$student->role}\n";
        
        $class = ClassModel::find($student->class_id);
        if ($class) {
            echo "   - Class Name: {$class->name}\n";
        } else {
            echo "   - ❌ Class not found for ID: {$student->class_id}\n";
        }
    } else {
        echo "❌ Student not found: {$regNumber}\n";
    }
    echo "\n";
}

// Check all students in each class
echo "🔍 STUDENTS BY CLASS\n";
echo "===================\n";

$classes = ClassModel::with('users')->get();
foreach ($classes as $class) {
    $studentCount = $class->users->where('role', 'student')->count();
    echo "Class {$class->id} ({$class->name}): {$studentCount} students\n";
    
    if ($studentCount > 0) {
        $sampleStudents = $class->users->where('role', 'student')->take(3);
        foreach ($sampleStudents as $student) {
            echo "  - {$student->registration_number}: {$student->name}\n";
        }
    }
    echo "\n";
}

echo "🔍 STEP 2: ADMIN DASHBOARD PAGES REVIEW\n";
echo "======================================\n";

$adminPages = [
    'Dashboard' => 'resources/views/admin/dashboard.blade.php',
    'Users Management' => 'resources/views/admin/users/index.blade.php',
    'Questions Management' => 'resources/views/admin/questions/index.blade.php',
    'Scoreboard' => 'resources/views/admin/scoreboard/index.blade.php',
    'Exam Reset' => 'resources/views/admin/exam-reset/index.blade.php',
    'System Reset' => 'resources/views/admin/system-reset/index.blade.php',
    'Security Management' => 'resources/views/admin/security/index.blade.php',
    'Admin Layout' => 'resources/views/layouts/admin.blade.php'
];

foreach ($adminPages as $pageName => $filePath) {
    if (file_exists($filePath)) {
        echo "✅ {$pageName}: EXISTS\n";
        
        $content = file_get_contents($filePath);
        
        // Check for local assets usage
        $localAssets = [
            'bootstrap.min.css' => strpos($content, "asset('assets/css/bootstrap.min.css')") !== false,
            'jquery local' => strpos($content, "asset('assets/js/jquery") !== false,
            'fontawesome local' => strpos($content, "asset('assets/css/fontawesome.min.css')") !== false,
            'datatables local' => strpos($content, "asset('assets/js/jquery.dataTables.min.js')") !== false
        ];
        
        foreach ($localAssets as $asset => $found) {
            if ($found) {
                echo "   ✅ Uses local {$asset}\n";
            } else {
                echo "   ❌ Missing local {$asset}\n";
            }
        }
        
        // Check for CDN dependencies
        $cdnUsage = [
            'jQuery CDN' => strpos($content, 'code.jquery.com') !== false,
            'Bootstrap CDN' => strpos($content, 'cdn.jsdelivr.net') !== false,
            'FontAwesome CDN' => strpos($content, 'cdnjs.cloudflare.com') !== false
        ];
        
        foreach ($cdnUsage as $cdn => $found) {
            if ($found) {
                echo "   ⚠️ Still uses {$cdn}\n";
            }
        }
        
    } else {
        echo "❌ {$pageName}: MISSING - {$filePath}\n";
    }
    echo "\n";
}

echo "🔍 STEP 3: LOCAL ASSETS AVAILABILITY\n";
echo "===================================\n";

$requiredAssets = [
    'CSS' => [
        'public/assets/css/bootstrap.min.css',
        'public/assets/css/fontawesome.min.css',
        'public/assets/css/dataTables.bootstrap4.min.css',
        'public/assets/css/tailwind.min.css'
    ],
    'JavaScript' => [
        'public/assets/js/jquery-3.6.0.min.js',
        'public/assets/js/jquery.dataTables.min.js',
        'public/assets/js/dataTables.bootstrap4.min.js'
    ]
];

foreach ($requiredAssets as $type => $assets) {
    echo "{$type} Assets:\n";
    foreach ($assets as $asset) {
        if (file_exists($asset)) {
            $size = filesize($asset);
            echo "  ✅ " . basename($asset) . " (" . number_format($size) . " bytes)\n";
        } else {
            echo "  ❌ " . basename($asset) . " - MISSING\n";
        }
    }
    echo "\n";
}

echo "🔍 STEP 4: CONTROLLER FUNCTIONALITY TEST\n";
echo "=======================================\n";

try {
    // Test Admin Dashboard Controller
    $adminController = new \App\Http\Controllers\Admin\AdminDashboardController();
    echo "✅ AdminDashboardController: ACCESSIBLE\n";
    
    // Test User Controller
    $userController = new \App\Http\Controllers\Admin\UserController();
    echo "✅ UserController: ACCESSIBLE\n";
    
    // Test Scoreboard Controller
    $scoreboardController = new \App\Http\Controllers\Admin\ScoreboardController();
    echo "✅ ScoreboardController: ACCESSIBLE\n";
    
    // Test Exam Reset Controller
    $examResetController = new \App\Http\Controllers\Admin\ExamResetController();
    echo "✅ ExamResetController: ACCESSIBLE\n";
    
    // Test search functionality with actual data
    echo "\n🔍 TESTING SEARCH FUNCTIONALITY\n";
    echo "==============================\n";
    
    $testStudent = User::where('role', 'student')->first();
    if ($testStudent) {
        echo "Testing with student: {$testStudent->registration_number}\n";
        
        // Simulate search request
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'registration_number' => $testStudent->registration_number,
            'class_id' => $testStudent->class_id
        ]);
        
        $searchResponse = $examResetController->searchStudent($request);
        $searchData = json_decode($searchResponse->getContent(), true);
        
        if ($searchData['success']) {
            echo "✅ Search works: Found {$searchData['student']['name']}\n";
        } else {
            echo "❌ Search failed: {$searchData['message']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Controller test failed: " . $e->getMessage() . "\n";
}

echo "\n🔍 STEP 5: DATABASE RELATIONSHIPS\n";
echo "=================================\n";

try {
    // Test User-Class relationship
    $userWithClass = User::with('classModel')->where('role', 'student')->first();
    if ($userWithClass && $userWithClass->classModel) {
        echo "✅ User-Class relationship: WORKING\n";
    } else {
        echo "❌ User-Class relationship: BROKEN\n";
    }
    
    // Test Class-Subject relationship
    $classWithSubjects = ClassModel::with('subjects')->first();
    if ($classWithSubjects && $classWithSubjects->subjects->count() > 0) {
        echo "✅ Class-Subject relationship: WORKING\n";
    } else {
        echo "❌ Class-Subject relationship: BROKEN\n";
    }
    
    // Test User-Score relationship
    $userWithScores = User::with('userScores')->where('role', 'student')->first();
    if ($userWithScores) {
        echo "✅ User-Score relationship: WORKING ({$userWithScores->userScores->count()} scores)\n";
    } else {
        echo "❌ User-Score relationship: BROKEN\n";
    }
    
} catch (Exception $e) {
    echo "❌ Relationship test failed: " . $e->getMessage() . "\n";
}

echo "\n🔍 STEP 6: ROUTE ACCESSIBILITY\n";
echo "=============================\n";

$adminRoutes = [
    'admin.dashboard',
    'admin.users.index',
    'admin.questions.index', 
    'admin.scoreboard.index',
    'admin.exam.reset.index',
    'admin.system.reset.index',
    'admin.security.index'
];

foreach ($adminRoutes as $route) {
    try {
        $url = route($route);
        echo "✅ {$route}: {$url}\n";
    } catch (Exception $e) {
        echo "❌ {$route}: ROUTE NOT FOUND\n";
    }
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
echo "Issues found will be fixed in the next steps...\n";

?>