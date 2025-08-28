<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->integer('score');
            $table->integer('total_questions');
            $table->integer('time_taken_seconds')->nullable();
            $table->timestamp('submission_time')->nullable(); // Or use created_at
            $table->timestamps();
            $table->unique(['user_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_scores');
    }
};
