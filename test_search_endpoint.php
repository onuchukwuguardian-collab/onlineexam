<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING SEARCH ENDPOINT ===\n";

try {
    // Test the search endpoint directly
    $controller = new \App\Http\Controllers\Admin\AdminExamResetController();
    
    // Create a mock request
    $request = new \Illuminate\Http\Request();
    $request->merge(['query' => 'em']);
    
    echo "Testing search with query 'em'...\n";
    
    $response = $controller->searchStudents($request);
    
    if ($response instanceof \Illuminate\Http\JsonResponse) {
        echo "✅ Controller returned JSON response\n";
        
        $data = $response->getData(true);
        echo "✅ Response data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($data['success']) && $data['success']) {
            echo "✅ Search was successful\n";
            echo "✅ Found " . count($data['students']) . " students\n";
        } else {
            echo "❌ Search failed: " . ($data['message'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Controller did not return JSON response\n";
        echo "Response type: " . get_class($response) . "\n";
    }
    
    // Test with different queries
    $testQueries = ['john', 'admin', 'test'];
    
    foreach ($testQueries as $query) {
        echo "\nTesting search with query '$query'...\n";
        $request = new \Illuminate\Http\Request();
        $request->merge(['query' => $query]);
        
        try {
            $response = $controller->searchStudents($request);
            $data = $response->getData(true);
            
            if (isset($data['success']) && $data['success']) {
                echo "✅ Found " . count($data['students']) . " students for '$query'\n";
            } else {
                echo "❌ Search failed for '$query': " . ($data['message'] ?? 'Unknown error') . "\n";
            }
        } catch (Exception $e) {
            echo "❌ Exception for '$query': " . $e->getMessage() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== TESTING COMPLETE ===\n";