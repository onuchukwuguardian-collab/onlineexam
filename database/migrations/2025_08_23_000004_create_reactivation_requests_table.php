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
        Schema::create('reactivation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('exam_ban_id')->constrained()->onDelete('cascade');
            $table->text('request_message');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_response')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->json('violation_history')->nullable(); // Store violation details
            $table->integer('ban_count')->default(1); // Track how many times banned
            $table->timestamps();
            
            // Ensure one pending request per user per subject
            $table->unique(['user_id', 'subject_id', 'status']);
            
            // Indexes for performance
            $table->index(['status']);
            $table->index(['requested_at']);
            $table->index(['user_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reactivation_requests');
    }
};