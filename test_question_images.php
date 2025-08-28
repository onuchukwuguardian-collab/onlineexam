<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Check questions with images
$questionsWithImages = App\Models\Question::whereNotNull('image_path')->get(['id', 'image_path']);

echo "Questions with images: " . $questionsWithImages->count() . "\n";

foreach ($questionsWithImages as $question) {
    echo "Question {$question->id}: {$question->image_path}\n";
    
    // Check if file exists
    $fullPath = storage_path('app/public/' . $question->image_path);
    echo "  File exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    echo "  Full path: {$fullPath}\n";
    echo "  URL: " . asset('storage/' . $question->image_path) . "\n\n";
}

// Check all questions
echo "\nAll questions count: " . App\Models\Question::count() . "\n";