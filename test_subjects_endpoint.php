<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ClassModel;
use App\Models\Subject;

echo "=== Testing Subjects Endpoint ===\n\n";

// Get all classes
$classes = ClassModel::all();
echo "Available Classes:\n";
foreach ($classes as $class) {
    echo "- ID: {$class->id}, Name: {$class->name}\n";
}

echo "\n";

// Test subjects for each class
foreach ($classes as $class) {
    echo "Subjects for Class '{$class->name}' (ID: {$class->id}):\n";
    
    $subjects = Subject::where('class_id', $class->id)->get(['id', 'name']);
    
    if ($subjects->isEmpty()) {
        echo "  No subjects found for this class\n";
    } else {
        foreach ($subjects as $subject) {
            echo "  - ID: {$subject->id}, Name: {$subject->name}\n";
        }
    }
    echo "\n";
}

// Test the controller method directly
echo "=== Testing Controller Method ===\n";
$controller = new \App\Http\Controllers\Admin\ExamResetController();

foreach ($classes as $class) {
    echo "Testing getSubjectsForClass({$class->id}) for '{$class->name}':\n";
    
    try {
        $response = $controller->getSubjectsForClass($class->id);
        $data = json_decode($response->getContent(), true);
        
        if (empty($data)) {
            echo "  No subjects returned\n";
        } else {
            foreach ($data as $subject) {
                echo "  - ID: {$subject['id']}, Name: {$subject['name']}\n";
            }
        }
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

echo "=== Test Complete ===\n";