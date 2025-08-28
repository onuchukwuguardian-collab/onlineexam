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
        Schema::create('exam_bans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->string('ban_reason');
            $table->text('violation_details'); // JSON details of all violations
            $table->integer('total_violations');
            $table->timestamp('banned_at');
            $table->foreignId('banned_by_admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_permanent')->default(true);
            $table->timestamp('ban_expires_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            // Ensure one ban per user per subject
            $table->unique(['user_id', 'subject_id']);
            
            // Indexes for performance
            $table->index(['user_id']);
            $table->index(['subject_id']);
            $table->index(['banned_at']);
            $table->index(['is_permanent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_bans');
    }
};