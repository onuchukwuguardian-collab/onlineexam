<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find a question to test with
$question = App\Models\Question::first();

if (!$question) {
    echo "No questions found in database\n";
    exit;
}

echo "Testing with question ID: {$question->id}\n";
echo "Current image path: " . ($question->image_path ?? 'NULL') . "\n";

// Check if the image file exists if there's a path
if ($question->image_path) {
    $fullPath = storage_path('app/public/' . $question->image_path);
    echo "Image file exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    echo "Full path: {$fullPath}\n";
    echo "URL: " . asset('storage/' . $question->image_path) . "\n";
}

// Check storage directory permissions
$storageDir = storage_path('app/public/question_images');
echo "\nStorage directory: {$storageDir}\n";
echo "Directory exists: " . (is_dir($storageDir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($storageDir) ? 'YES' : 'NO') . "\n";

// List files in question_images directory
echo "\nFiles in question_images directory:\n";
$files = glob($storageDir . '/*');
foreach ($files as $file) {
    echo "  " . basename($file) . " (" . filesize($file) . " bytes)\n";
}