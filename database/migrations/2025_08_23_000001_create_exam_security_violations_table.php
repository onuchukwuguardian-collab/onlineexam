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
        Schema::create('exam_security_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_session_id')->nullable()->constrained()->onDelete('set null');
            $table->string('violation_type'); // 'tab_switch', 'window_blur', 'copy_attempt', etc.
            $table->text('description');
            $table->json('metadata')->nullable(); // Additional data like user agent, IP, etc.
            $table->timestamp('occurred_at');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'subject_id']);
            $table->index(['violation_type']);
            $table->index(['occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_security_violations');
    }
};