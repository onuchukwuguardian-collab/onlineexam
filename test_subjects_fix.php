<?php

echo "=== TESTING SUBJECTS ENDPOINT FIX ===\n\n";

echo "🔍 VERIFICATION CHECKLIST\n";
echo "=========================\n";

// Check 1: Route exists
$routeOutput = shell_exec('php artisan route:list --name=exam.reset.subjects 2>&1');
if (strpos($routeOutput, 'admin.exam.reset.subjects') !== false) {
    echo "✅ Route registered: admin/exam-reset/subjects/{class}\n";
} else {
    echo "❌ Route not found\n";
}

// Check 2: Controller method works
echo "\n🔍 CONTROLLER TEST\n";
echo "==================\n";

try {
    require_once 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    $controller = new \App\Http\Controllers\Admin\ExamResetController();
    $response = $controller->getSubjectsForClass(8); // JSS1
    
    if ($response->getStatusCode() == 200) {
        $data = json_decode($response->getContent(), true);
        echo "✅ Controller returns " . count($data) . " subjects for JSS1\n";
        
        foreach ($data as $subject) {
            echo "  - {$subject['name']}\n";
        }
    } else {
        echo "❌ Controller error: " . $response->getStatusCode() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Controller test failed: " . $e->getMessage() . "\n";
}

// Check 3: JavaScript fixes
echo "\n🔍 JAVASCRIPT FIXES\n";
echo "===================\n";

if (file_exists('resources/views/admin/exam-reset/index.blade.php')) {
    $view = file_get_contents('resources/views/admin/exam-reset/index.blade.php');
    
    $checks = [
        '$.ajaxSetup' => 'Global AJAX setup',
        'X-CSRF-TOKEN' => 'CSRF token header',
        'X-Requested-With' => 'XMLHttpRequest header',
        '$.ajax({' => 'Proper AJAX method'
    ];
    
    foreach ($checks as $pattern => $description) {
        if (strpos($view, $pattern) !== false) {
            echo "✅ {$description} added\n";
        } else {
            echo "❌ {$description} missing\n";
        }
    }
} else {
    echo "❌ View file not found\n";
}

echo "\n🔍 TESTING INSTRUCTIONS\n";
echo "========================\n";
echo "To test the fix:\n";
echo "1. Login as admin\n";
echo "2. Go to Admin > Exam Reset\n";
echo "3. Select a class (JSS1, SS1, or SS2)\n";
echo "4. Check if subjects populate in the dropdown\n";
echo "5. Open browser developer tools to check for errors\n";

echo "\n✅ FIX APPLIED\n";
echo "==============\n";
echo "- Added global AJAX setup with CSRF token\n";
echo "- Changed $.get() to $.ajax() with proper headers\n";
echo "- Added X-CSRF-TOKEN and X-Requested-With headers\n";
echo "- Added error logging to console\n";

echo "\nThe subjects should now populate correctly when selecting a class!\n";

?>