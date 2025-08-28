<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->longText('answers_json')->nullable(); // Store selected answers as JSON
            $table->integer('current_question_idx')->default(0);
            $table->bigInteger('original_start_time'); // Unix timestamp
            $table->timestamp('last_activity_at')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_progress');
    }
};
