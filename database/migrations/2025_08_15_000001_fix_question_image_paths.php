<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all questions with image paths that start with 'questions/'
        $questions = DB::table('questions')
            ->whereNotNull('image_path')
            ->where('image_path', 'like', 'questions/%')
            ->get();

        foreach ($questions as $question) {
            $oldPath = $question->image_path;
            $filename = basename($oldPath);
            $newPath = 'question_images/' . $filename;
            
            // Check if old file exists
            if (Storage::disk('public')->exists($oldPath)) {
                // Move file to new location
                if (!Storage::disk('public')->exists($newPath)) {
                    Storage::disk('public')->move($oldPath, $newPath);
                }
                
                // Update database record
                DB::table('questions')
                    ->where('id', $question->id)
                    ->update(['image_path' => $newPath]);
                    
                echo "Moved image for question {$question->id}: {$oldPath} -> {$newPath}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get all questions with image paths that start with 'question_images/'
        $questions = DB::table('questions')
            ->whereNotNull('image_path')
            ->where('image_path', 'like', 'question_images/%')
            ->get();

        foreach ($questions as $question) {
            $oldPath = $question->image_path;
            $filename = basename($oldPath);
            $newPath = 'questions/' . $filename;
            
            // Check if old file exists
            if (Storage::disk('public')->exists($oldPath)) {
                // Move file back to old location
                if (!Storage::disk('public')->exists($newPath)) {
                    Storage::disk('public')->move($oldPath, $newPath);
                }
                
                // Update database record
                DB::table('questions')
                    ->where('id', $question->id)
                    ->update(['image_path' => $newPath]);
            }
        }
    }
};