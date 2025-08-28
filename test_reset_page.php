<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING RESET PAGE ACCESS ===\n";

try {
    // Test if the route exists
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $resetRoute = null;
    
    foreach ($routes as $route) {
        if ($route->getName() === 'admin.exam.reset.index') {
            $resetRoute = $route;
            break;
        }
    }
    
    if ($resetRoute) {
        echo "✅ Reset route found: " . $resetRoute->uri() . "\n";
        echo "✅ Route methods: " . implode(', ', $resetRoute->methods()) . "\n";
    } else {
        echo "❌ Reset route not found\n";
    }
    
    // Test controller exists
    if (class_exists('App\Http\Controllers\Admin\AdminExamResetController')) {
        echo "✅ AdminExamResetController exists\n";
        
        $controller = new \App\Http\Controllers\Admin\AdminExamResetController();
        if (method_exists($controller, 'index')) {
            echo "✅ index method exists\n";
        } else {
            echo "❌ index method missing\n";
        }
    } else {
        echo "❌ AdminExamResetController not found\n";
    }
    
    // Test view exists
    if (view()->exists('admin.exam_reset.index')) {
        echo "✅ Reset view exists\n";
    } else {
        echo "❌ Reset view not found\n";
    }
    
    // Test data for view
    $totalStudents = \App\Models\User::where('role', 'student')->count();
    $totalSubjects = \App\Models\Subject::count();
    $totalScores = \App\Models\UserScore::count();
    $activeSessions = \App\Models\ExamSession::where('is_active', true)->count();
    
    echo "\n=== DATA SUMMARY ===\n";
    echo "Total Students: $totalStudents\n";
    echo "Total Subjects: $totalSubjects\n";
    echo "Total Scores: $totalScores\n";
    echo "Active Sessions: $activeSessions\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== TESTING COMPLETE ===\n";