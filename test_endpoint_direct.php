<?php

echo "=== TESTING SUBJECTS ENDPOINT DIRECTLY ===\n\n";

// Bootstrap Laravel
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test the controller method directly
echo "🔍 TESTING CONTROLLER METHOD DIRECTLY\n";
echo "=====================================\n";

try {
    $controller = new \App\Http\Controllers\Admin\ExamResetController();
    
    // Test with class ID 8 (JSS1)
    $classId = 8;
    echo "Testing with class ID: {$classId}\n";
    
    $response = $controller->getSubjectsForClass($classId);
    $content = $response->getContent();
    
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response content: " . $content . "\n";
    
    $data = json_decode($content, true);
    if (is_array($data)) {
        echo "✅ Valid JSON response\n";
        echo "Subjects found: " . count($data) . "\n";
        
        foreach ($data as $subject) {
            echo "  - ID: {$subject['id']}, Name: {$subject['name']}\n";
        }
    } else {
        echo "❌ Invalid JSON response\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test database query directly
echo "\n🔍 TESTING DATABASE QUERY DIRECTLY\n";
echo "==================================\n";

try {
    $subjects = \App\Models\Subject::where('class_id', 8)
        ->orderBy('name')
        ->get(['id', 'name']);
    
    echo "Direct database query results:\n";
    echo "Subjects found: " . $subjects->count() . "\n";
    
    foreach ($subjects as $subject) {
        echo "  - ID: {$subject->id}, Name: {$subject->name}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Check all classes and their subjects
echo "\n🔍 ALL CLASSES AND SUBJECTS\n";
echo "===========================\n";

try {
    $classes = \App\Models\ClassModel::with('subjects')->get();
    
    foreach ($classes as $class) {
        echo "Class {$class->id}: {$class->name} ({$class->subjects->count()} subjects)\n";
        foreach ($class->subjects as $subject) {
            echo "  - {$subject->name}\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>