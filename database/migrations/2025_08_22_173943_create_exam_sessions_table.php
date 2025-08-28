<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('expires_at');
            $table->integer('duration_minutes');
            $table->json('answers')->nullable(); // Store current answers
            $table->integer('current_question_index')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_submitted')->default(false);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            
            // Ensure one active session per user per subject
            $table->unique(['user_id', 'subject_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
