<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== FIXING QUESTION MANAGEMENT ISSUES ===\n";

try {
    echo "\n1. FIXING STORAGE SYMLINK\n";
    
    $publicStoragePath = public_path('storage');
    $storagePublicPath = storage_path('app/public');
    
    // Remove existing link if it's broken
    if (is_link($publicStoragePath)) {
        if (!is_dir($publicStoragePath)) {
            unlink($publicStoragePath);
            echo "✅ Removed broken storage symlink\n";
        }
    }
    
    // Create new symlink if needed
    if (!is_link($publicStoragePath) && !is_dir($publicStoragePath)) {
        try {
            symlink($storagePublicPath, $publicStoragePath);
            echo "✅ Created storage symlink\n";
        } catch (Exception $e) {
            echo "❌ Failed to create symlink: " . $e->getMessage() . "\n";
            
            // Alternative: create directory and copy files
            if (!is_dir($publicStoragePath)) {
                mkdir($publicStoragePath, 0755, true);
                echo "✅ Created storage directory as fallback\n";
            }
        }
    }
    
    // Verify symlink works
    $testFile = $storagePublicPath . '/test.txt';
    file_put_contents($testFile, 'test');
    
    $publicTestFile = $publicStoragePath . '/test.txt';
    if (file_exists($publicTestFile)) {
        echo "✅ Storage symlink working correctly\n";
        unlink($testFile);
    } else {
        echo "❌ Storage symlink not working\n";
    }
    
    echo "\n2. ENSURING QUESTION_IMAGES DIRECTORY EXISTS\n";
    
    $questionImagesPath = storage_path('app/public/question_images');
    if (!is_dir($questionImagesPath)) {
        mkdir($questionImagesPath, 0755, true);
        echo "✅ Created question_images directory\n";
    } else {
        echo "✅ Question_images directory exists\n";
    }
    
    // Check permissions
    if (is_writable($questionImagesPath)) {
        echo "✅ Question_images directory is writable\n";
    } else {
        chmod($questionImagesPath, 0755);
        echo "✅ Fixed question_images directory permissions\n";
    }
    
    echo "\n3. TESTING QUESTION CREATION WITH PROPER ERROR HANDLING\n";
    
    // Find a subject to test with
    $testSubject = \App\Models\Subject::first();
    
    if (!$testSubject) {
        echo "❌ No subjects found for testing\n";
        exit;
    }
    
    echo "✅ Testing with subject: {$testSubject->name}\n";
    
    // Test creating a question with validation
    try {
        \Illuminate\Support\Facades\DB::beginTransaction();
        
        $question = \App\Models\Question::create([
            'subject_id' => $testSubject->id,
            'question_text' => 'Test question: What is the capital of France?',
            'correct_answer' => 'A',
            'image_path' => null
        ]);
        
        echo "✅ Question created successfully (ID: {$question->id})\n";
        
        // Create options
        $options = [
            ['letter' => 'A', 'text' => 'Paris'],
            ['letter' => 'B', 'text' => 'London'],
            ['letter' => 'C', 'text' => 'Berlin'],
            ['letter' => 'D', 'text' => 'Madrid']
        ];
        
        foreach ($options as $optionData) {
            $option = \App\Models\Option::create([
                'question_id' => $question->id,
                'option_letter' => $optionData['letter'],
                'option_text' => $optionData['text']
            ]);
            echo "✅ Option {$optionData['letter']} created: {$optionData['text']}\n";
        }
        
        \Illuminate\Support\Facades\DB::commit();
        echo "✅ Question and options saved successfully\n";
        
        // Test updating the question
        $question->update(['question_text' => 'Updated: What is the capital of France?']);
        echo "✅ Question updated successfully\n";
        
        // Test deleting the question
        $question->options()->delete();
        $question->delete();
        echo "✅ Question and options deleted successfully\n";
        
    } catch (Exception $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        echo "❌ Question creation/update error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n4. TESTING IMAGE UPLOAD FUNCTIONALITY\n";
    
    try {
        // Create a test image
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8A');
        
        $tempImagePath = sys_get_temp_dir() . '/test_question_image.jpg';
        file_put_contents($tempImagePath, $testImageContent);
        
        if (file_exists($tempImagePath)) {
            echo "✅ Test image created\n";
            
            // Test storing image
            $filename = time() . '_test_upload.jpg';
            $storagePath = 'question_images/' . $filename;
            $fullStoragePath = storage_path('app/public/' . $storagePath);
            
            if (copy($tempImagePath, $fullStoragePath)) {
                echo "✅ Image stored successfully\n";
                
                // Test if image is accessible via URL
                $imageUrl = asset('storage/' . $storagePath);
                echo "✅ Image URL: {$imageUrl}\n";
                
                // Test if file exists via storage facade
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($storagePath)) {
                    echo "✅ Image accessible via Storage facade\n";
                } else {
                    echo "❌ Image not accessible via Storage facade\n";
                }
                
                // Clean up
                unlink($fullStoragePath);
                echo "✅ Test image cleaned up\n";
            } else {
                echo "❌ Failed to store image\n";
            }
            
            // Clean up temp file
            unlink($tempImagePath);
        } else {
            echo "❌ Failed to create test image\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Image upload test error: " . $e->getMessage() . "\n";
    }
    
    echo "\n5. CHECKING CSRF TOKEN AVAILABILITY\n";
    
    // Ensure CSRF token is available
    try {
        $token = csrf_token();
        if ($token) {
            echo "✅ CSRF token available\n";
        } else {
            echo "❌ CSRF token not available\n";
        }
    } catch (Exception $e) {
        echo "❌ CSRF token error: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. TESTING VALIDATION SCENARIOS\n";
    
    // Test various validation scenarios
    $validationTests = [
        [
            'name' => 'Empty question text',
            'data' => [
                'question_text' => '',
                'correct_answer' => 'A',
                'options' => [
                    ['letter' => 'A', 'text' => 'Option A'],
                    ['letter' => 'B', 'text' => 'Option B']
                ]
            ],
            'should_fail' => true
        ],
        [
            'name' => 'Invalid correct answer',
            'data' => [
                'question_text' => 'Test question?',
                'correct_answer' => 'Z',
                'options' => [
                    ['letter' => 'A', 'text' => 'Option A'],
                    ['letter' => 'B', 'text' => 'Option B']
                ]
            ],
            'should_fail' => true
        ],
        [
            'name' => 'No options',
            'data' => [
                'question_text' => 'Test question?',
                'correct_answer' => 'A',
                'options' => []
            ],
            'should_fail' => true
        ],
        [
            'name' => 'Valid question',
            'data' => [
                'question_text' => 'What is 2 + 2?',
                'correct_answer' => 'A',
                'options' => [
                    ['letter' => 'A', 'text' => '4'],
                    ['letter' => 'B', 'text' => '3']
                ]
            ],
            'should_fail' => false
        ]
    ];
    
    foreach ($validationTests as $test) {
        $validator = \Illuminate\Support\Facades\Validator::make($test['data'], [
            'question_text' => 'required|string|max:65000',
            'correct_answer' => 'required|string|in:A,B,C,D,E',
            'options' => 'required|array|min:2|max:5',
            'options.*.letter' => ['required', 'string', \Illuminate\Validation\Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'options.*.text' => 'required|string|max:1000',
        ]);
        
        $failed = $validator->fails();
        
        if ($test['should_fail'] && $failed) {
            echo "✅ {$test['name']}: Validation correctly failed\n";
        } elseif (!$test['should_fail'] && !$failed) {
            echo "✅ {$test['name']}: Validation correctly passed\n";
        } else {
            echo "❌ {$test['name']}: Validation result unexpected\n";
            if ($failed) {
                foreach ($validator->errors()->all() as $error) {
                    echo "   - {$error}\n";
                }
            }
        }
    }
    
    echo "\n7. CREATING DIAGNOSTIC INFORMATION\n";
    
    // Create a diagnostic file
    $diagnosticInfo = [
        'timestamp' => now()->toDateTimeString(),
        'storage_path' => storage_path('app/public'),
        'public_storage_path' => public_path('storage'),
        'question_images_path' => storage_path('app/public/question_images'),
        'storage_link_exists' => is_link(public_path('storage')),
        'storage_dir_exists' => is_dir(public_path('storage')),
        'question_images_writable' => is_writable(storage_path('app/public/question_images')),
        'php_upload_max_filesize' => ini_get('upload_max_filesize'),
        'php_post_max_size' => ini_get('post_max_size'),
        'php_memory_limit' => ini_get('memory_limit'),
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION
    ];
    
    file_put_contents(storage_path('logs/question_management_diagnostic.json'), json_encode($diagnosticInfo, JSON_PRETTY_PRINT));
    echo "✅ Diagnostic information saved to storage/logs/question_management_diagnostic.json\n";
    
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIXES COMPLETE ===\n";