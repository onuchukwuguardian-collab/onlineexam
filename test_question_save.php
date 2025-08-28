<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TESTING QUESTION SAVE FUNCTIONALITY ===\n";

try {
    echo "\n1. QUICK STORAGE FIX\n";
    
    // Ensure question_images directory exists and is writable
    $questionImagesPath = storage_path('app/public/question_images');
    if (!is_dir($questionImagesPath)) {
        mkdir($questionImagesPath, 0755, true);
        echo "✅ Created question_images directory\n";
    }
    
    if (!is_writable($questionImagesPath)) {
        chmod($questionImagesPath, 0755);
        echo "✅ Fixed question_images permissions\n";
    }
    
    echo "\n2. TESTING BASIC QUESTION CREATION\n";
    
    // Find a subject to test with
    $testSubject = \App\Models\Subject::first();
    
    if (!$testSubject) {
        echo "❌ No subjects found for testing\n";
        exit;
    }
    
    echo "✅ Testing with subject: {$testSubject->name}\n";
    
    // Test creating a simple question
    try {
        \Illuminate\Support\Facades\DB::beginTransaction();
        
        $questionData = [
            'subject_id' => $testSubject->id,
            'question_text' => 'Test Question: What is the capital of Nigeria?',
            'correct_answer' => 'A',
            'image_path' => null
        ];
        
        $question = \App\Models\Question::create($questionData);
        echo "✅ Question created successfully (ID: {$question->id})\n";
        
        // Create options
        $optionsData = [
            ['question_id' => $question->id, 'option_letter' => 'A', 'option_text' => 'Abuja'],
            ['question_id' => $question->id, 'option_letter' => 'B', 'option_text' => 'Lagos'],
            ['question_id' => $question->id, 'option_letter' => 'C', 'option_text' => 'Kano'],
            ['question_id' => $question->id, 'option_letter' => 'D', 'option_text' => 'Port Harcourt']
        ];
        
        foreach ($optionsData as $optionData) {
            $option = \App\Models\Option::create($optionData);
            echo "✅ Option {$optionData['option_letter']} created: {$optionData['option_text']}\n";
        }
        
        \Illuminate\Support\Facades\DB::commit();
        echo "✅ Question and options committed to database\n";
        
        // Test updating the question
        $question->update(['question_text' => 'Updated: What is the capital of Nigeria?']);
        echo "✅ Question updated successfully\n";
        
        // Test retrieving the question with options
        $retrievedQuestion = \App\Models\Question::with('options')->find($question->id);
        echo "✅ Question retrieved with " . $retrievedQuestion->options->count() . " options\n";
        
        // Clean up
        $retrievedQuestion->options()->delete();
        $retrievedQuestion->delete();
        echo "✅ Test question cleaned up\n";
        
    } catch (Exception $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        echo "❌ Question creation failed: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
    
    echo "\n3. TESTING CONTROLLER STORE METHOD\n";
    
    // Test the actual controller store method
    try {
        $controller = new \App\Http\Controllers\Admin\QuestionController();
        
        // Create a mock request
        $request = new \Illuminate\Http\Request();
        $request->merge([
            'question_text' => 'Controller Test: What is 5 + 5?',
            'correct_answer' => 'B',
            'options' => [
                ['letter' => 'A', 'text' => '9'],
                ['letter' => 'B', 'text' => '10'],
                ['letter' => 'C', 'text' => '11']
            ]
        ]);
        
        // Mock CSRF token
        $request->headers->set('X-CSRF-TOKEN', csrf_token());
        
        echo "✅ Mock request created\n";
        echo "✅ Question text: " . $request->input('question_text') . "\n";
        echo "✅ Correct answer: " . $request->input('correct_answer') . "\n";
        echo "✅ Options count: " . count($request->input('options')) . "\n";
        
        // Note: We can't easily test the full store method without proper HTTP context
        // But we can test the validation logic
        
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'question_text' => 'required|string|max:65000',
            'correct_answer' => 'required|string|in:A,B,C,D,E',
            'options' => 'required|array|min:2|max:5',
            'options.*.letter' => ['required', 'string', \Illuminate\Validation\Rule::in(['A', 'B', 'C', 'D', 'E'])],
            'options.*.text' => 'required|string|max:1000',
        ]);
        
        if ($validator->passes()) {
            echo "✅ Controller validation would pass\n";
        } else {
            echo "❌ Controller validation would fail:\n";
            foreach ($validator->errors()->all() as $error) {
                echo "   - {$error}\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Controller test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n4. CHECKING COMMON ISSUES\n";
    
    // Check CSRF token availability
    try {
        $token = csrf_token();
        echo "✅ CSRF token available: " . substr($token, 0, 10) . "...\n";
    } catch (Exception $e) {
        echo "❌ CSRF token issue: " . $e->getMessage() . "\n";
    }
    
    // Check storage disk
    try {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $testFile = 'test_' . time() . '.txt';
        $disk->put($testFile, 'test content');
        
        if ($disk->exists($testFile)) {
            echo "✅ Storage disk working\n";
            $disk->delete($testFile);
        } else {
            echo "❌ Storage disk not working\n";
        }
    } catch (Exception $e) {
        echo "❌ Storage disk error: " . $e->getMessage() . "\n";
    }
    
    // Check database connection
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        echo "✅ Database connection working\n";
    } catch (Exception $e) {
        echo "❌ Database connection error: " . $e->getMessage() . "\n";
    }
    
    echo "\n5. RECOMMENDATIONS\n";
    
    echo "Based on the tests above, here are the likely issues and fixes:\n\n";
    
    echo "🔧 IMMEDIATE FIXES TO TRY:\n";
    echo "1. Clear browser cache and cookies\n";
    echo "2. Check browser console for JavaScript errors\n";
    echo "3. Ensure you're clicking 'Save' button after making changes\n";
    echo "4. Try creating a simple question without image first\n";
    echo "5. Check if CSRF token is being sent with AJAX requests\n\n";
    
    echo "🔧 IF IMAGES NOT UPLOADING:\n";
    echo "1. Run: php artisan storage:link\n";
    echo "2. Check file permissions on storage/app/public/question_images\n";
    echo "3. Verify image file size is under 2MB\n";
    echo "4. Use only JPEG, PNG, or JPG formats\n\n";
    
    echo "🔧 IF AJAX ERRORS PERSIST:\n";
    echo "1. Check Laravel logs: storage/logs/laravel.log\n";
    echo "2. Enable debug mode in .env: APP_DEBUG=true\n";
    echo "3. Check network tab in browser developer tools\n";
    echo "4. Verify routes are properly defined\n\n";
    
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== TESTING COMPLETE ===\n";