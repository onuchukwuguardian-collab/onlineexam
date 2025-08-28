<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add enhanced offender tracking fields to exam_bans table
     */
    public function up(): void
    {
        Schema::table('exam_bans', function (Blueprint $table) {
            // Enhanced tracking fields
            $table->string('violation_type')->nullable()->after('ban_reason');
            $table->integer('ban_count')->default(1)->after('total_violations');
            $table->json('subject_specific_data')->nullable()->after('admin_notes');
            
            // Reactivation tracking
            $table->boolean('is_active')->default(true)->after('ban_expires_at');
            $table->timestamp('reactivated_at')->nullable()->after('is_active');
            $table->foreignId('reactivated_by')->nullable()->constrained('users')->onDelete('set null')->after('reactivated_at');
            $table->text('reactivation_reason')->nullable()->after('reactivated_by');
            
            // Add indexes for enhanced tracking
            $table->index(['violation_type']);
            $table->index(['ban_count']);
            $table->index(['is_active']);
            $table->index(['reactivated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_bans', function (Blueprint $table) {
            $table->dropIndex(['reactivated_at']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['ban_count']);
            $table->dropIndex(['violation_type']);
            
            $table->dropForeign(['reactivated_by']);
            $table->dropColumn([
                'violation_type',
                'ban_count',
                'subject_specific_data',
                'is_active',
                'reactivated_at',
                'reactivated_by',
                'reactivation_reason'
            ]);
        });
    }
};