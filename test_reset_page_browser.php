<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING RESET PAGE IN BROWSER ===\n";

try {
    // Simulate a request to the reset page
    $request = \Illuminate\Http\Request::create('/admin/exam-reset', 'GET');
    $controller = new \App\Http\Controllers\Admin\AdminExamResetController();
    
    // Call the index method
    $response = $controller->index();
    
    if ($response instanceof \Illuminate\View\View) {
        echo "✅ Controller returned a view\n";
        echo "✅ View name: " . $response->getName() . "\n";
        
        $data = $response->getData();
        echo "✅ View data keys: " . implode(', ', array_keys($data)) . "\n";
        
        // Check specific data
        if (isset($data['totalStudents'])) {
            echo "✅ Total Students: " . $data['totalStudents'] . "\n";
        }
        if (isset($data['totalSubjects'])) {
            echo "✅ Total Subjects: " . $data['totalSubjects'] . "\n";
        }
        if (isset($data['totalScores'])) {
            echo "✅ Total Scores: " . $data['totalScores'] . "\n";
        }
        if (isset($data['activeSessions'])) {
            echo "✅ Active Sessions: " . $data['activeSessions'] . "\n";
        }
        
        // Try to render the view
        try {
            $html = $response->render();
            echo "✅ View rendered successfully\n";
            echo "✅ HTML length: " . strlen($html) . " characters\n";
            
            // Check for key elements
            if (strpos($html, 'selectResetType') !== false) {
                echo "✅ JavaScript function selectResetType found\n";
            } else {
                echo "❌ JavaScript function selectResetType NOT found\n";
            }
            
            if (strpos($html, 'Individual Reset') !== false) {
                echo "✅ Individual Reset section found\n";
            }
            
            if (strpos($html, 'Bulk Reset') !== false) {
                echo "✅ Bulk Reset section found\n";
            }
            
        } catch (Exception $e) {
            echo "❌ View rendering failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Controller did not return a view\n";
        echo "Response type: " . get_class($response) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== TESTING COMPLETE ===\n";